<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exception\UserTokenFollowException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\WithdrawLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManager;
use App\Manager\DonationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Manager\WithdrawalLocksManager;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Security\DisabledServicesVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\NetworkSymbolConverterInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Exception\IncorrectAddressException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * @Rest\Route("/api/wallet")
 */
class WalletController extends APIController implements TwoFactorAuthenticatedInterface
{
    private const WALLET_ITEMS_BATCH_SIZE = 11;

    private TranslatorInterface $translations;
    private string $coinifySharedSecret;
    private MailerInterface $mailer;
    protected CryptoManagerInterface $cryptoManager;
    private UserManagerInterface $userManager;
    protected TokenManagerInterface $tokenManager;
    private UserTokenManagerInterface $userTokenManager;
    private EntityManagerInterface $em;
    private NetworkSymbolConverterInterface $networkSymbolConverter;
    protected SessionInterface $session;
    private WalletInterface $wallet;
    private UserTokenFollowManagerInterface $userTokenFollowManager;
    private RebrandingConverterInterface $rebrandingConverter;
    private PendingWithdrawRepository $pendingWithdrawRepository;
    private PendingTokenWithdrawRepository $pendingTokenWithdrawRepository;
    private WithdrawalLocksManager $withdrawalLocksManager;
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;
    private WithdrawLogger $withdrawLogger;
    private ValidatorFactoryInterface $validatorFactory;

    use ViewOnlyTrait;

    public function __construct(
        TranslatorInterface $translations,
        MailerInterface $mailer,
        CryptoManagerInterface $cryptoManager,
        UserManagerInterface $userManager,
        string $coinifySharedSecret,
        TokenManagerInterface $tokenManager,
        UserTokenManagerInterface $userTokenManager,
        EntityManagerInterface $em,
        NetworkSymbolConverterInterface $networkSymbolConverter,
        SessionInterface $session,
        WalletInterface $wallet,
        UserTokenFollowManagerInterface $userTokenFollowManager,
        RebrandingConverterInterface $rebrandingConverter,
        PendingWithdrawRepository $pendingWithdrawRepository,
        PendingTokenWithdrawRepository $pendingTokenWithdrawRepository,
        WithdrawalLocksManager $withdrawalLocksManager,
        WithdrawalDelaysConfig $withdrawalDelaysConfig,
        WithdrawLogger $withdrawLogger,
        ValidatorFactoryInterface $validatorFactory
    ) {
        $this->translations = $translations;
        $this->coinifySharedSecret = $coinifySharedSecret;
        $this->mailer = $mailer;
        $this->cryptoManager = $cryptoManager;
        $this->userManager = $userManager;
        $this->tokenManager = $tokenManager;
        $this->userTokenManager = $userTokenManager;
        $this->em = $em;
        $this->networkSymbolConverter = $networkSymbolConverter;
        $this->session = $session;
        $this->wallet = $wallet;
        $this->userTokenFollowManager = $userTokenFollowManager;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->pendingWithdrawRepository = $pendingWithdrawRepository;
        $this->pendingTokenWithdrawRepository = $pendingTokenWithdrawRepository;
        $this->withdrawalLocksManager = $withdrawalLocksManager;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
        $this->withdrawLogger = $withdrawLogger;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/history/{page}",
     *     name="payment_history",
     *     requirements={"page"="^[0-9]\d*$"},
     *     defaults={"page"=1},
     *     options={"expose"=true}
     *     )
     * @return mixed[]
     */
    public function getDepositWithdrawHistory(int $page): array
    {
        /** @var User $user*/
        $user = $this->getUser();

        return $this->wallet->getWithdrawDepositHistory(
            $user,
            ($page - 1) * self::WALLET_ITEMS_BATCH_SIZE,
            self::WALLET_ITEMS_BATCH_SIZE
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/withdraw", name="withdraw")
     * @Rest\RequestParam(name="currency", allowBlank=false)
     * @Rest\RequestParam(name="cryptoNetwork", allowBlank=false)
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(
     *      name="address",
     *      allowBlank=false,
     *      requirements="^[a-zA-Z0-9]+$"
     *     )
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function withdraw(
        ParamFetcherInterface $request,
        TokenManagerInterface $tokenManager,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        if (!$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $this->translations->trans('page.settings_invalid_2fa'));
        }

        /** @var User $user*/
        $user = $this->getUser();

        $currencyParam = $request->get('currency');
        $cryptoNetworkParam = $request->get('cryptoNetwork');
        $amountParam = $request->get('amount');
        $addressParam = $request->get('address');

        if (!$this->isGranted('make-withdrawal')) {
            throw $this->createAccessDeniedException();
        }

        $this->validateProperties(null, [(string)$amountParam], new ApiBadRequestException('Invalid starting price'));

        $tradable = $this->cryptoManager->findBySymbol($currencyParam)
            ?? $tokenManager->findByName($currencyParam);

        if (!$tradable) {
            return $this->view([
                'error' => $this->translations->trans('api.wallet.not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $pendingWithdrawals = $tradable instanceof Crypto
            ? $this->pendingWithdrawRepository->getPendingByCrypto($user, $tradable)
            : $this->pendingTokenWithdrawRepository->getPendingByToken($user, $tradable);

        if (count($pendingWithdrawals) > 0) {
            return $this->view(
                ['message' => $this->translations->trans(
                    'wallet.already_have_pending_withdrawal',
                    ['%symbol%' => $this->rebrandingConverter->convert($tradable->getSymbol())]
                ),],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($tradable instanceof Crypto) {
            if (!$this->isGranted(DisabledServicesVoter::COIN_WITHDRAW, $tradable)) {
                throw new ApiBadRequestException();
            }
        }

        $cryptoNetworkSymbol = $this->networkSymbolConverter->convert($cryptoNetworkParam);
        $cryptoNetwork = $this->cryptoManager->findBySymbol($cryptoNetworkSymbol);

        $this->denyAccessUnlessGranted('not-blocked', $tradable instanceof Token ? $tradable : null);

        if ($tradable instanceof Crypto) {
            $this->denyAccessUnlessGranted(DisabledServicesVoter::COIN_WITHDRAW);
            $this->denyAccessUnlessGranted('not-disabled', $tradable);

            if (!$cryptoNetwork || !$tradable->canBeWithdrawnTo($cryptoNetwork)) {
                return $this->createInvalidNetworkException();
            }
        }

        if ($tradable instanceof Token) {
            $this->denyAccessUnlessGranted(DisabledServicesVoter::TOKEN_WITHDRAW, $tradable);

            if (!$cryptoNetwork || !$tradable->getDeployByCrypto($cryptoNetwork)) {
                return $this->createInvalidNetworkException();
            }
        }

        $cryptoNetworkName = $this->cryptoManager->getNetworkName($cryptoNetwork->getSymbol());

        if (!$cryptoNetwork->isBlockchainAvailable()) {
            return $this->view([
                'error' => $this->translations->trans('blockchain_unavailable', [
                    'blockchainName' => $cryptoNetworkName,
                ]),
            ], Response::HTTP_BAD_REQUEST);
        }

        $amountDecimals = (int) strpos(strrev($amountParam), ".");
        $tradableDecimals = $tradable->getShowSubunit();

        if ($amountDecimals > $tradableDecimals) {
            return $this->view([
                'error' => $this->translations->trans(
                    'post_form.msg.amount.max_decimals',
                    ['%maxDecimals%' => $tradableDecimals]
                ),
            ], Response::HTTP_BAD_REQUEST);
        }

        $amount = $moneyWrapper->parse(
            $amountParam,
            $tradable instanceof Token ? Symbols::TOK : $tradable->getSymbol()
        );

        $customMinAmountValidator = $this->validatorFactory->createCustomMinWithdrawalValidator($amount, $tradable);

        if (!$customMinAmountValidator->validate()) {
            return $this->view(
                ['error' => $customMinAmountValidator->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $lockWithdrawalDelayError = $this->withdrawalLocksManager->prepareDelayLocks(
            $user->getId(),
            $user->isGoogleAuthenticatorEnabled()
        );

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
                new Address(trim((string)$addressParam)),
                new Amount($amount),
                $tradable,
                $cryptoNetwork
            );
        } catch (Throwable $exception) {
            $this->withdrawLogger->error("error while withdrawing", [
                'user' => $user->getEmail(),
                'currency' => $currencyParam,
                'cryptoNetwork' => $cryptoNetworkParam,
                'amount' => $amountParam,
                'address' => $addressParam,
                'errorMessage' => $exception->getMessage(),
                'stackTrace' => $exception->getTraceAsString(),
            ]);

            if ($exception instanceof IncorrectAddressException) {
                return $this->view(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->view(
                ['error' => $this->translations->trans('api.wallet.withdrawal_failed')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($user->isGoogleAuthenticatorEnabled()) {
            try {
                $this->wallet->withdrawCommit($pendingWithdraw);

                $this->withdrawLogger->info(
                    'Withdrawal request sent to queue for'. " " .$pendingWithdraw->getSymbol(),
                    [
                        'address' => $pendingWithdraw->getAddress()->getAddress(),
                        'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
                        'fee' => $pendingWithdraw->getFee()->getAmount(),
                    ]
                );
            } catch (Throwable $exception) {
                return $this->view([
                    'error' => $this->translations->trans('api.wallet.withdrawal_went_wrong'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $this->mailer->sendWithdrawConfirmationMail($user, $pendingWithdraw, $tradable, $cryptoNetworkName);

            $this->withdrawLogger->info(
                'Sent withdrawal email for'. " " .$tradable->getSymbol(),
                [
                    'address' => $pendingWithdraw->getAddress()->getAddress(),
                    'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        $this->withdrawalLocksManager->releaseLockBalance();

        return $this->view([
            'amount' => $moneyWrapper->format($pendingWithdraw->getAmount()->getAmount()),
            'fee' => $moneyWrapper->format($pendingWithdraw->getFee()),
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/addresses/signature", name="deposit_addresses_signature", options={"expose"=true})
     */
    public function getDepositAddressesSignature(): View
    {
        $this->denyAccessUnlessGranted(
            'make-deposit',
            null,
            $this->translations->trans(
                'api.add_phone_number_message'
            )
        );

        /** @var User $user*/
        $user = $this->getUser();
        $allCrypto = $this->cryptoManager->findAll();
        $crypto = array_filter($allCrypto, fn(Crypto $crypto) =>
            !$crypto->isToken()
            && $this->isGranted(DisabledServicesVoter::COIN_DEPOSIT, $crypto));

        $cryptoDepositAddresses = !$user->isBlocked() && $this->isGranted(DisabledServicesVoter::COIN_DEPOSIT)
            ? $this->wallet->getDepositCredentials(
                $user,
                $crypto
            )
            : [];

        $tokenDepositAddresses = $this->isGranted(DisabledServicesVoter::TOKEN_DEPOSIT)
            ? $this->wallet->getTokenDepositCredentials($user)
            : [];

        $addresses = array_merge($cryptoDepositAddresses, $tokenDepositAddresses);
        $signatures = [];

        /**
         * @var string $symbol
         * @var Address $address
         */
        foreach ($addresses as $symbol => $address) {
            $signatures[$symbol] = hash_hmac(
                'sha256',
                $address->getAddress(),
                $this->coinifySharedSecret
            );
        }

        return $this->view([
            'addresses' => $addresses,
            'signatures' => $signatures,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/deposit-credentials/{currency}",
     *     name="deposit_credentials",
     *     options={"expose"=true}
     * )
     * @Rest\QueryParam(name="token", nullable=true, default=null)
     */
    public function getDepositCredentials(string $currency, ParamFetcherInterface $request): View
    {
        $this->denyAccessUnlessGranted(DisabledServicesVoter::COIN_DEPOSIT);

        $this->denyAccessUnlessGranted(
            'make-deposit',
            null,
            $this->translations->trans(
                'api.add_phone_number_message'
            )
        );

        /** @var User $user*/
        $user = $this->getUser();

        if ($user->isBlocked()) {
            throw new ApiForbiddenException();
        }

        $crypto = $this->cryptoManager->findBySymbol($currency);

        if (!$crypto) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted(DisabledServicesVoter::COIN_DEPOSIT, $crypto);

        return $this->view($this->wallet->getDepositCredential($user, $crypto));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/deposit/{currency}/info", name="deposit_info", options={"expose"=true})
     * @Rest\QueryParam(name="cryptoNetwork", allowBlank=true)
     */
    public function getDepositInfo(
        string $currency,
        TokenManagerInterface $tokenManager,
        ParamFetcherInterface $request
    ): View {
        $tradable = $this->cryptoManager->findBySymbol($currency) ?? $tokenManager->findByName($currency);

        if (!$tradable) {
            return $this->view([
                'error' => $this->translations->trans('api.wallet.not_found_currency'),
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $cryptoNetworkSymbol = $request->get('cryptoNetwork');
        $cryptoNetwork = $cryptoNetworkSymbol
            ? $this->cryptoManager->findBySymbol($cryptoNetworkSymbol)
            : null;

        if (!$cryptoNetwork) {
            return $this->createInvalidNetworkException();
        }

        if ($tradable instanceof Crypto) {
            $this->denyAccessUnlessGranted(DisabledServicesVoter::COIN_DEPOSIT, $tradable);

            if (!$tradable->canBeWithdrawnTo($cryptoNetwork)) {
                return $this->createInvalidNetworkException();
            }
        }

        if ($tradable instanceof Token) {
            $this->denyAccessUnlessGranted(DisabledServicesVoter::TOKEN_DEPOSIT, $tradable);

            if (!$tradable->getDeployByCrypto($cryptoNetwork)) {
                return $this->createInvalidNetworkException();
            }
        }

        /** @var User $user*/
        $user = $this->getUser();

        return $this->view($this->wallet->getDepositInfo($tradable, $cryptoNetwork, $user));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/withdraw/{symbol}/info", name="withdraw_info", options={"expose"=true})
     * @Rest\QueryParam(name="cryptoNetwork", allowBlank=true)
     */
    public function getWithdrawInfo(
        string $symbol,
        TokenManagerInterface $tokenManager,
        ParamFetcherInterface $request
    ): View {
        $tradable = $this->cryptoManager->findBySymbol($symbol) ?? $tokenManager->findByName($symbol);

        if (!$tradable) {
            return $this->view([
                'error' => $this->translations->trans('api.wallet.not_found_currency'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $cryptoNetworkSymbol = $request->get('cryptoNetwork');
        $cryptoNetwork = $cryptoNetworkSymbol
            ? $this->cryptoManager->findBySymbol($cryptoNetworkSymbol)
            : null;

        if (!$cryptoNetwork) {
            return $this->createInvalidNetworkException();
        }

        $this->denyAccessUnlessGranted(
            $tradable instanceof Crypto ? DisabledServicesVoter::COIN_WITHDRAW : DisabledServicesVoter::TOKEN_WITHDRAW,
            $tradable
        );

        return $this->view($this->wallet->getWithdrawInfo($cryptoNetwork, $tradable));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/referral", name="referral_balance", options={"expose"=true})
     */
    public function getReferralBalance(
        BalanceHandlerInterface $balanceHandler,
        TokenManagerInterface $tokenManager,
        DonationManagerInterface $donationManager
    ): View {
        $cryptos = [
            Symbols::WEB => $this->cryptoManager->findBySymbol(Symbols::WEB),
        ];

        $cryptoValues = array_values($cryptos);

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $referralBalances = $balanceHandler->getReferralBalances($user, $cryptoValues);
        $referralRewards = $tokenManager->getUserAllDeployTokensReward($user, $cryptoValues);
        $mintmeRewardDonations = $donationManager->getDonationReferralRewards($user);

        $allBalances = [];

        foreach (array_keys($referralBalances) as $symbol) {
            $allBalances[$symbol] = $referralBalances[$symbol]->add($referralRewards[$symbol]);

            if (Symbols::WEB === $symbol) {
                $allBalances[$symbol] = $allBalances[$symbol]->add($mintmeRewardDonations);
            }
        }

        return $this->view([
            'balances' => $allBalances,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/sent-exchange-mintme-mail", name="send_exchange_mintme_mail", options={"expose"=true})
     */
    public function sendExchangeMintmeMail(): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isExchangeCryptoMailSent()) {
            throw new ApiBadRequestException();
        }

        $this->userManager->sendMintmeExchangeMail($user);

        return $this->view([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Delete("/token/{name}/delete", name="token_wallet_delete", options={"expose"=true})
     */
    public function deleteToken(string $name): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        $token = $this->tokenManager->findByUrl($name);

        if (!$token) {
            throw new ApiNotFoundException($this->translations->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('delete-from-wallet', $token);

        $userToken = $this->userTokenManager->findByUserToken($user, $token);

        if (!$userToken) {
            throw new ApiNotFoundException(
                $this->translations->trans('api.tokens.user_has_not_token', ['%name%' => $name])
            );
        }

        $userToken->setIsRemoved(true);
        $this->em->flush();

        try {
            $this->userTokenFollowManager->manualUnfollow($token, $user);
        } catch (UserTokenFollowException $exception) {
        }

        return $this->view(
            ['message' => $this->translations->trans('api.tokens.delete_successfull')],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/withdraw-delays", name="withdraw_delays", options={"expose"=true})
     */
    public function withdrawDelays(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        $withdrawAfterLoginTime = $this->withdrawalDelaysConfig->getWithdrawAfterLoginTime();
        $withdrawAfterRegisterTime = $this->withdrawalDelaysConfig->getWithdrawAfterRegisterTime();

        $loginDelayPassed = $this->withdrawalLocksManager->isLoginLockExpired($user->getId());
        $registrationDelayPassed = $this->withdrawalLocksManager->isRegisterLockExpired($user->getId());

        return $this->view([
            'login' => [
                'passed' => $loginDelayPassed,
                'errorMsg' => $this->translations->trans('toasted.error.withdrawals.after.login', [
                    '%secondsTotal%' => $withdrawAfterLoginTime,
                ]),
            ],
            'registration' => [
                'passed' => $registrationDelayPassed,
                'errorMsg' => $this->translations->trans('toasted.error.withdrawals.after.register', [
                    '%hours%' => round($withdrawAfterRegisterTime / 3600, 3),
                ]),
            ],
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\GET("/check-crypto-address", name="check_crypto_address", options={"expose"=true})
     * @Rest\QueryParam(name="symbol", allowBlank=false)
     * @Rest\QueryParam(name="address", allowBlank=false)
     */
    public function checkCryptoAddress(ParamFetcherInterface $request): View
    {
        $symbol = $request->get('symbol');
        $address = $request->get('address');

        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (!$crypto || !is_string($address)) {
            return $this->view(false, Response::HTTP_OK);
        }

        $validator = $this->validatorFactory->createAddressValidator($crypto, $address);

        return $this->view($validator->validate(), Response::HTTP_OK);
    }

    private function createInvalidNetworkException(): View
    {
        return $this->view([
            'error' => $this->translations->trans('api.wallet.invalid_network'),
        ], Response::HTTP_BAD_REQUEST);
    }
}
