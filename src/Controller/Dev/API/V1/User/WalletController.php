<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1\User;

use App\Controller\Dev\API\V1\DevApiController;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\WithdrawalNotificationStrategy;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\LockFactory;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Utils\Validator\TradebleDigitsValidator;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @Rest\Route(path="/dev/api/v1/user/wallet")
 */
class WalletController extends DevApiController
{
    /** @var WalletInterface */
    private $wallet;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var ValidatorFactoryInterface */
    private $vf;

    private UserNotificationManagerInterface $userNotificationManager;

    private TranslatorInterface $translator;

    public function __construct(
        WalletInterface $wallet,
        UserActionLogger $userActionLogger,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        ValidatorFactoryInterface $validatorFactory,
        UserNotificationManagerInterface $userNotificationManager,
        TranslatorInterface $translator
    ) {
        $this->wallet = $wallet;
        $this->userActionLogger = $userActionLogger;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->vf = $validatorFactory;
        $this->userNotificationManager = $userNotificationManager;
        $this->translator = $translator;
    }

    /**
     * List users wallet deposit addresses.
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/addresses")
     * @SWG\Response(
     *     response="200",
     *     description="Returns wallet deposit addresses related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Address")
     *     )
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositAddresses(WalletInterface $depositCommunicator): array
    {
        $this->denyAccessUnlessGranted('deposit');

        if (!$this->isGranted('make-deposit')) {
            return [
                'status' => 'error',
                'message' => $this->translator->trans('api.add_phone_number_message'),
            ];
        }

        /** @var User $user */
        $user = $this->getUser();

        $cryptoDepositAddresses = !$user->isBlocked() ? $depositCommunicator->getDepositCredentials(
            $user,
            $crypto = array_filter($this->cryptoManager->findAll(), fn(Crypto $crypto) => !$crypto->isToken())
        ) : [];

        $isBlockedToken = $user->getProfile()->hasBlockedTokens();

        $tokenDepositAddress = !$isBlockedToken ? $depositCommunicator->getTokenDepositCredentials($user) : [];

        $rebrandedAddresses = [];

        foreach (array_merge($cryptoDepositAddresses, $tokenDepositAddress) as $symbol => $address) {
            $rebrandedAddresses[$this->rebrandingConverter->convert((string)$symbol)] = $address;
        }

        return $rebrandedAddresses;
    }

    /**
     * List deposit/withdraw history.
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/history")
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements=@Assert\Range(min="0"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101]")
     * @SWG\Response(
     *     response="200",
     *     description="Returns wallet deposit/withdraw history related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Transaction")
     *     )
     * )
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositWithdrawHistory(ParamFetcherInterface $request): array
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->wallet->getWithdrawDepositHistory(
            $user,
            (int)$request->get('offset'),
            (int)$request->get('limit')
        );
    }

    /**
     * Withdraw to specific address
     *
     * @Rest\View()
     * @Rest\Post("/withdraw")
     * @Rest\RequestParam(name="currency", allowBlank=false)
     * @Rest\RequestParam(
     *     name="amount",
     *     allowBlank=false
     * )
     * @Rest\RequestParam(
     *     name="address",
     *     allowBlank=false,
     *     requirements="^[a-zA-Z0-9]+$"
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      description="JSON Payload",
     *      required=true,
     *      format="application/json",
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="currency", type="string", example="MINTME", description="currency to withdraw"),
     *          @SWG\Property(property="amount", type="string", example="12.33", description="Amount to withdraw"),
     *          @SWG\Property(property="address", type="string", example="0x0..0", description="address to withdraw to"),
     *      )
     * ),
     * @SWG\Response(response="201", description="Returns success message")
     * @SWG\Response(response="404", description="Currency not found")
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     */
    public function withdraw(
        ParamFetcherInterface $request,
        MoneyWrapperInterface $moneyWrapper,
        MailerInterface $mailer,
        LockFactory $lockFactory
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $lock = $lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        $this->denyAccessUnlessGranted('withdraw');

        if (!$this->isGranted('make-withdrawal')) {
            return $this->view([
                'message' => $this->translator->trans('api.add_phone_number_message'),
            ], Response::HTTP_OK);
        }

        $currency = $request->get('currency');
        $amount = $request->get('amount');
        $address = $request->get('address');

        $this->checkForDisallowedValues($currency);

        $currency = $this->rebrandingConverter->reverseConvert(
            mb_strtolower($currency)
        );

        $tradable = $this->cryptoManager->findBySymbol($currency)
            ?? $this->tokenManager->findByName($currency);

        if (!$tradable) {
            throw new ApiNotFoundException('Currency not found');
        }

        if ($tradable instanceof Token) {
            $this->denyAccessUnlessGranted('token-withdraw');
        }

        $this->denyAccessUnlessGranted('not-blocked', $tradable instanceof Token ? $tradable : null);

        if (!($validator = new TradebleDigitsValidator($amount, $tradable))->validate()) {
            throw new ApiBadRequestException($validator->getMessage());
        }

        $validator = $this->vf->createMinAmountValidator($tradable, $amount);

        if (!$validator->validate()) {
            throw new ApiBadRequestException(
                $this->rebrandingConverter->convert(
                    $validator->getMessage()
                )
            );
        }

        $validator = $this->vf->createAddressValidator($tradable, $address);

        if (!$validator->validate()) {
            throw new ApiBadRequestException(
                $this->rebrandingConverter->convert(
                    $validator->getMessage()
                )
            );
        }

        /** @var  User $user*/
        $user = $this->getUser();

        try {
            $pendingWithdraw = $this->wallet->withdrawInit(
                $user,
                new Address(trim((string)$address)),
                new Amount($moneyWrapper->parse(
                    $amount,
                    $tradable instanceof Token ? Symbols::TOK : $tradable->getSymbol()
                )),
                $tradable
            );

            $this->denyAccessUnlessGranted('edit', $pendingWithdraw);

            $this->wallet->withdrawCommit($pendingWithdraw);
        } catch (Throwable $exception) {
            throw new ApiBadRequestException('Withdrawal failed');
        }

        $notificationType = NotificationTypes::WITHDRAWAL;
        $strategy = new WithdrawalNotificationStrategy(
            $this->userNotificationManager,
            $notificationType
        );
        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($user);

        $this->userActionLogger->info("Withdraw funds from API for {$pendingWithdraw->getSymbol()}.", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
        ]);

        $lock->release();

        return $this->view([
            'message' => "Your transaction has been successfully processed and queued to be sent.",
        ], Response::HTTP_CREATED);
    }
}
