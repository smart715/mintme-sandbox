<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @Rest\Route("/api/wallet")
 */
class WalletController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    private const DEPOSIT_WITHDRAW_HISTORY_LIMIT = 100;

    private UserActionLogger $userActionLogger;
    private TranslatorInterface $translations;
    private string $coinifySharedSecret;
    private LockFactory $lockFactory;
    private MailerInterface $mailer;
    private CryptoManagerInterface $cryptoManager;
    private UserManagerInterface $userManager;
    private LoggerInterface $logger;

    public function __construct(
        TranslatorInterface $translations,
        UserActionLogger $userActionLogger,
        LockFactory $lockFactory,
        MailerInterface $mailer,
        CryptoManagerInterface $cryptoManager,
        UserManagerInterface $userManager,
        LoggerInterface $logger,
        string $coinifySharedSecret
    ) {
        $this->translations = $translations;
        $this->userActionLogger = $userActionLogger;
        $this->lockFactory = $lockFactory;
        $this->coinifySharedSecret = $coinifySharedSecret;
        $this->mailer = $mailer;
        $this->cryptoManager = $cryptoManager;
        $this->userManager = $userManager;
        $this->logger = $logger;
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
    public function getDepositWithdrawHistory(
        int $page,
        WalletInterface $wallet
    ): array {
        /** @var User $user*/
        $user = $this->getUser();

        return $wallet->getWithdrawDepositHistory(
            $user,
            ($page - 1) * self::DEPOSIT_WITHDRAW_HISTORY_LIMIT,
            self::DEPOSIT_WITHDRAW_HISTORY_LIMIT
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/withdraw", name="withdraw", options={"2fa"="optional"})
     * @Rest\RequestParam(name="crypto", allowBlank=false)
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(
     *      name="address",
     *      allowBlank=false,
     *      requirements="^[a-zA-Z0-9]+$"
     *     )
     * @Rest\RequestParam(name="code", allowBlank=true)
     */
    public function withdraw(
        ParamFetcherInterface $request,
        TokenManagerInterface $tokenManager,
        MoneyWrapperInterface $moneyWrapper,
        WalletInterface $wallet
    ): View {
        /** @var User $user*/
        $user = $this->getUser();
        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$user->getId());

        if (!$lock->acquire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isGranted('make-withdrawal')) {
            throw $this->createAccessDeniedException();
        }

        $tradable = $this->cryptoManager->findBySymbol($request->get('crypto'))
            ?? $tokenManager->findByName($request->get('crypto'));

        if (!$tradable) {
            return $this->view([
                'error' => $this->translations->trans('api.wallet.not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->denyAccessUnlessGranted('not-blocked', $tradable instanceof Token ? $tradable : null);
        $this->denyAccessUnlessGranted('withdraw');

        if ($tradable instanceof Crypto) {
            $this->denyAccessUnlessGranted('not-disabled', $tradable);
        }

        try {
            $pendingWithdraw = $wallet->withdrawInit(
                $user,
                new Address(trim((string)$request->get('address'))),
                new Amount($moneyWrapper->parse(
                    $request->get('amount'),
                    $tradable instanceof Token ? Symbols::TOK : $tradable->getSymbol()
                )),
                $tradable
            );
        } catch (Throwable $exception) {
            $this->logger->error('error while withdrawing: ' . json_encode($exception));

            return $this->view([
                'error' => $this->translations->trans('api.wallet.withdrawal_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($user->isGoogleAuthenticatorEnabled()) {
            try {
                $wallet->withdrawCommit($pendingWithdraw);

                $this->userActionLogger->info(
                    'Withdrawal request sent to queue for'. " " .$pendingWithdraw->getSymbol(),
                    [
                        'address' => $pendingWithdraw->getAddress()->getAddress(),
                        'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
                    ]
                );
            } catch (Throwable $exception) {
                return $this->view([
                    'error' => $this->translations->trans('api.wallet.withdrawal_went_wrong'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $this->mailer->sendWithdrawConfirmationMail($user, $pendingWithdraw);

            $this->userActionLogger->info(
                'Sent withdrawal email for'. " " .$tradable->getSymbol(),
                [
                    'address' => $pendingWithdraw->getAddress()->getAddress(),
                    'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
                ]
            );
        }

        $lock->release();

        return $this->view();
    }

    /**
     * @Rest\View()
     * @Rest\Get("/addresses/signature", name="deposit_addresses_signature", options={"expose"=true})
     */
    public function getDepositAddressesSignature(WalletInterface $depositCommunicator): View
    {
        $this->denyAccessUnlessGranted('deposit');

        /** @var User $user*/
        $user = $this->getUser();

        $allCrypto = $this->cryptoManager->findAll();
        $crypto = array_filter($allCrypto, fn(Crypto $crypto) => !$crypto->isToken());

        $cryptoDepositAddresses = !$user->isBlocked() ? $depositCommunicator->getDepositCredentials(
            $user,
            $crypto
        ) : [];

        $tokenDepositAddresses = $depositCommunicator->getTokenDepositCredentials($user);

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
     * @Rest\Get("/deposit/{crypto}/info", name="deposit_info", options={"expose"=true})
     */
    public function getDepositInfo(
        string $crypto,
        WalletInterface $depositCommunicator,
        TokenManagerInterface $tokenManager
    ): View {
        $this->denyAccessUnlessGranted('deposit');

        /** @var TradebleInterface|null $tradable */
        $tradable = $this->cryptoManager->findBySymbol($crypto) ?? $tokenManager->findByName($crypto);

        if (!$tradable) {
            return $this->view([
                'error' => $this->translations->trans('api.wallet.not_found_currency'),
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        return $this->view($depositCommunicator->getDepositInfo($tradable));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/referral", name="referral_balance", options={"expose"=true})
     */
    public function getReferralBalance(
        BalanceHandlerInterface $balanceHandler,
        TokenManagerInterface $tokenManager
    ): View {
        $webToken = $tokenManager->findByName(Symbols::WEB);

        if (!$webToken) {
            throw new InvalidArgumentException();
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $referralBalance = $balanceHandler->balance($user, $webToken)->getReferral();
        $referralReward = $tokenManager->getUserDeployTokensReward($user);

        return $this->view([
            'balance' => $referralBalance->add($referralReward),
            'token' => $webToken,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/sent-exchange-mintme-mail", name="send_exchange_mintme_mail", options={"expose"=true})
     */
    public function sendExchangeMintmeMail(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isExchangeCryptoMailSent()) {
            throw new ApiBadRequestException();
        }

        $this->userManager->sendMintmeExchangeMail($user);

        return $this->view([], Response::HTTP_NO_CONTENT);
    }
}
