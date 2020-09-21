<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\User;

use App\Controller\Dev\API\DevApiController;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v1/user/wallet")
 */
class WalletController extends DevApiController
{
    /** @var float */
    private $withdrawExpirationTime;

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

    public function __construct(
        float $withdrawExpirationTime,
        WalletInterface $wallet,
        UserActionLogger $userActionLogger,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        ValidatorFactoryInterface $validatorFactory
    ) {
        $this->withdrawExpirationTime = floor($withdrawExpirationTime / 3600);
        $this->wallet = $wallet;
        $this->userActionLogger = $userActionLogger;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->vf = $validatorFactory;
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
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositAddresses(WalletInterface $depositCommunicator): array
    {
        /** @var  User $user*/
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('not-blocked');

        $cryptoDepositAddresses = !$user->isBlocked() ? $depositCommunicator->getDepositCredentials(
            $user,
            $this->cryptoManager->findAll()
        ) : [];

        $isBlockedToken = $user->getProfile()->getToken()
            ? $user->getProfile()->getToken()->isBlocked()
            : false;

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
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositWithdrawHistory(ParamFetcherInterface $request): array
    {
        /** @var  User $user*/
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
     * * @Rest\RequestParam(
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
     * @SWG\Response(response="201",description="Returns success message")
     * @SWG\Response(response="404",description="Currency not found")
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="User Wallet")
     */
    public function withdraw(
        ParamFetcherInterface $request,
        MoneyWrapperInterface $moneyWrapper,
        MailerInterface $mailer
    ): View {
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

        $this->denyAccessUnlessGranted('not-blocked', $tradable instanceof Token ? $tradable : null);

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
                    $tradable instanceof Token ? MoneyWrapper::TOK_SYMBOL : $tradable->getSymbol()
                )),
                $tradable
            );
        } catch (\Throwable $exception) {
            throw new ApiBadRequestException('Withdrawal failed');
        }

        $mailer->sendWithdrawConfirmationMail($user, $pendingWithdraw);

        $this->userActionLogger->info("Sent withdrawal email for {$tradable->getSymbol()}", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
        ]);

        return $this->view([
            'message' => "Confirmation email has been sent to your email. It will expire in {$this->withdrawExpirationTime} hours.",
        ], Response::HTTP_ACCEPTED);
    }
}
