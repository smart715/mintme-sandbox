<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1\User;

use App\Controller\Dev\API\V1\DevApiController;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Logger\WithdrawLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\WithdrawalLocksManager;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\WithdrawalNotificationStrategy;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Security\DisabledServicesVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\NetworkSymbolConverterInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\NotificationTypes;
use App\Utils\Validator\TradableDigitsValidator;
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
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * @Rest\Route(path="/dev/api/v1/user/wallet")
 */
class WalletController extends DevApiController
{
    private WalletInterface $wallet;
    private RebrandingConverterInterface $rebrandingConverter;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private ValidatorFactoryInterface $vf;
    private UserNotificationManagerInterface $userNotificationManager;
    private TranslatorInterface $translator;
    private NetworkSymbolConverterInterface $networkSymbolConverter;
    private PendingWithdrawRepository $pendingWithdrawRepository;
    private PendingTokenWithdrawRepository $pendingTokenWithdrawRepository;
    private WithdrawalLocksManager $withdrawalLocksManager;
    private WithdrawLogger $withdrawLogger;

    public function __construct(
        WalletInterface $wallet,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        ValidatorFactoryInterface $validatorFactory,
        UserNotificationManagerInterface $userNotificationManager,
        TranslatorInterface $translator,
        NetworkSymbolConverterInterface $networkSymbolConverter,
        PendingWithdrawRepository $pendingWithdrawRepository,
        PendingTokenWithdrawRepository $pendingTokenWithdrawRepository,
        WithdrawalLocksManager $withdrawalLocksManager,
        WithdrawLogger $withdrawLogger
    ) {
        $this->wallet = $wallet;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->vf = $validatorFactory;
        $this->userNotificationManager = $userNotificationManager;
        $this->translator = $translator;
        $this->networkSymbolConverter = $networkSymbolConverter;
        $this->pendingWithdrawRepository = $pendingWithdrawRepository;
        $this->pendingTokenWithdrawRepository = $pendingTokenWithdrawRepository;
        $this->withdrawalLocksManager = $withdrawalLocksManager;
        $this->withdrawLogger = $withdrawLogger;
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
     * @SWG\Response(response="401", description="Unauthorized")
     * @SWG\Response(response="503", description="Service unavailable")
     * @SWG\Tag(name="User Wallet")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getDepositAddresses(WalletInterface $depositCommunicator): View
    {
        if (!$this->isGranted('make-deposit')) {
            return $this->view([
                'status' => 'error',
                'message' => $this->translator->trans('api.add_phone_number_message'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = $this->getUser();

        $allCrypto = $this->cryptoManager->findAll();
        $crypto = array_filter($allCrypto, fn(Crypto $crypto) =>
            !$crypto->isToken()
            && $this->isGranted(DisabledServicesVoter::COIN_DEPOSIT, $crypto));

        try {
            $cryptoDepositAddresses = !$user->isBlocked() && $this->isGranted(DisabledServicesVoter::COIN_DEPOSIT)
                ? $depositCommunicator->getDepositCredentials(
                    $user,
                    $crypto
                )
                : [];

            $tokenDepositAddress = $this->isGranted(DisabledServicesVoter::TOKEN_DEPOSIT)
                ? $depositCommunicator->getTokenDepositCredentials($user)
                : [];
            $rebrandedAddresses = [];

            foreach (array_merge($cryptoDepositAddresses, $tokenDepositAddress) as $symbol => $address) {
                $rebrandedAddresses[$this->rebrandingConverter->convert((string)$symbol)] = $address;
            }

            return $this->view($rebrandedAddresses, Response::HTTP_OK);
        } catch (\Throwable $err) {
            $this->withdrawLogger->error("Fetching addresses from Dev API for {$user->getEmail()} resulted in error", [
                'message' => $err->getMessage(),
            ]);

            return $this->view([
                'error' => $this->translator->trans('toasted.error.service_unavailable_short'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
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
     * @Rest\RequestParam(
     *     name="network",
     *     nullable=true,
     *     allowBlank=true,
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
     *          @SWG\Property(property="network", type="string", example="", description="Network where to withdraw to.<br>Only required for tokens, MINTME and USDC"),
     *      )
     * ),
     * @SWG\Response(response="201", description="Returns success message")
     * @SWG\Response(response="404", description="Currency not found")
     * @SWG\Response(response="400", description="Bad request")
     * @SWG\Tag(name="User Wallet")
     */
    public function withdraw(
        ParamFetcherInterface $request,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('make-withdrawal')) {
            return $this->view([
                'message' => $this->translator->trans('api.add_phone_number_message'),
            ], Response::HTTP_OK);
        }

        $currency = $request->get('currency');
        $amount = $request->get('amount');
        $address = $request->get('address');
        $cryptoNetworkParam = $request->get('network');

        $this->checkForDisallowedValues($currency);

        $currency = $this->rebrandingConverter->reverseConvert(
            mb_strtolower($currency)
        );

        $tradable = $this->cryptoManager->findBySymbol($currency)
            ?? $this->tokenManager->findByName($currency);

        if (!$tradable) {
            throw new ApiNotFoundException('Currency not found');
        }

        $pendingWithdrawals = $tradable instanceof Crypto
            ? $this->pendingWithdrawRepository->getPendingByCrypto($user, $tradable)
            : $this->pendingTokenWithdrawRepository->getPendingByToken($user, $tradable);

        if (count($pendingWithdrawals) > 0) {
            return $this->view(
                ['message' => $this->translator->trans(
                    'wallet.already_have_pending_withdrawal',
                    ['%symbol%' => $this->rebrandingConverter->convert($tradable->getSymbol())]
                ),],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($tradable instanceof Crypto) {
            if (!$this->isGranted(DisabledServicesVoter::COIN_WITHDRAW, $tradable)) {
                throw new ApiBadRequestException($this->translator->trans('wallet.withdraw_disabled'));
            }
        }

        $this->denyAccessUnlessGranted('not-blocked', $tradable instanceof Token ? $tradable : null);

        $cryptoNetwork = $this->getCryptoNetwork($tradable, $cryptoNetworkParam);

        if ($tradable instanceof Crypto) {
            $this->denyAccessUnlessGranted('not-disabled', $tradable);
            $this->denyAccessUnlessGranted(DisabledServicesVoter::COIN_WITHDRAW);

            if (!$cryptoNetwork || !$tradable->canBeWithdrawnTo($cryptoNetwork)) {
                throw new ApiBadRequestException($this->translator->trans('api.wallet.invalid_network'));
            }
        }

        if ($tradable instanceof Token) {
            $this->denyAccessUnlessGranted(DisabledServicesVoter::TOKEN_WITHDRAW, $tradable);

            if (!$cryptoNetwork || !$tradable->getDeployByCrypto($cryptoNetwork)) {
                throw new ApiBadRequestException($this->translator->trans('api.wallet.invalid_network'));
            }
        }

        if (!$cryptoNetwork->isBlockchainAvailable()) {
            return $this->view([
                'error' => $this->translator->trans('blockchain_unavailable', [
                    'blockchainName' => $this->cryptoManager->getNetworkName($cryptoNetwork->getSymbol()),
                ]),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!($validator = new TradableDigitsValidator($amount, $tradable))->validate()) {
            throw new ApiBadRequestException($validator->getMessage());
        }

        $amountDecimals = (int)strpos(strrev($request->get('amount')), ".");
        $tradableDecimals = $tradable->getShowSubunit();

        if ($amountDecimals > $tradableDecimals) {
            return $this->view([
                'error' => $this->translator->trans(
                    'post_form.msg.amount.max_decimals',
                    ['%maxDecimals%' => $tradableDecimals]
                ),
            ], Response::HTTP_BAD_REQUEST);
        }

        $validator = $this->vf->createMinAmountValidator($tradable, $amount);

        if (!$validator->validate()) {
            throw new ApiBadRequestException(
                $this->rebrandingConverter->convert(
                    $validator->getMessage()
                )
            );
        }

        $validator = $this->vf->createAddressValidator($cryptoNetwork, $address);

        if (!$validator->validate()) {
            throw new ApiBadRequestException(
                $this->rebrandingConverter->convert(
                    $validator->getMessage()
                )
            );
        }

        $amountMoneyObj = $moneyWrapper->parse(
            $amount,
            $tradable->getMoneySymbol()
        );
        $validator = $this->vf->createCustomMinWithdrawalValidator($amountMoneyObj, $tradable);

        if (!$validator->validate()) {
            return $this->view(
                ['error' => $validator->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $lockWithdrawalDelayError = $this->withdrawalLocksManager->prepareDelayLocks($user->getId());

        if ($lockWithdrawalDelayError) {
            return $this->view(
                ['message' => $lockWithdrawalDelayError],
                Response::HTTP_FORBIDDEN
            );
        }

        if (!$this->withdrawalLocksManager->acquireLockBalance($user->getId())) {
            $this->createAccessDeniedException();
        }

        try {
            $pendingWithdraw = $this->wallet->withdrawInit(
                $user,
                new Address(trim((string)$address)),
                new Amount($amountMoneyObj),
                $tradable,
                $cryptoNetwork
            );

            $this->denyAccessUnlessGranted('edit', $pendingWithdraw);

            $this->wallet->withdrawCommit($pendingWithdraw);
        } catch (Throwable $exception) {
            $this->withdrawLogger->error("error while withdrawing (API request)", [
                'user' => $user->getEmail(),
                'currency' => $currency,
                'cryptoNetwork' => $cryptoNetworkParam,
                'amount' => $amount,
                'address' => $address,
                'errorMessage' => $exception->getMessage(),
                'stackTrace' => $exception->getTraceAsString(),
            ]);

            throw new ApiBadRequestException($exception->getMessage() ?: 'Withdrawal failed');
        }

        $notificationType = NotificationTypes::WITHDRAWAL;
        $strategy = new WithdrawalNotificationStrategy(
            $this->userNotificationManager,
            $notificationType
        );
        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($user);

        $this->withdrawLogger->info("Withdraw funds from API for {$pendingWithdraw->getSymbol()}.", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
        ]);

        $this->withdrawalLocksManager->releaseLockBalance();

        return $this->view([
            'message' => "Your transaction has been successfully processed and queued to be sent.",
        ], Response::HTTP_CREATED);
    }

    private function getCryptoNetwork(TradableInterface $tradable, ?string $networkSymbol): ?Crypto
    {
        if ($networkSymbol) {
            return $this->cryptoManager->findBySymbol($this->networkSymbolConverter->convert($networkSymbol));
        }

        if ($tradable instanceof Token && $tradable->isDeployed() && 1 === count($tradable->getDeploys())) {
            return $tradable->getMainDeploy()->getCrypto();
        }

        if ($tradable instanceof Crypto) {
            $cryptoNetworks = $this->cryptoManager->getCryptoNetworks($tradable, true);

            if (1 === count($cryptoNetworks)) {
                return $cryptoNetworks[0]->getNetworkInfo();
            }
        }

        return null;
    }
}
