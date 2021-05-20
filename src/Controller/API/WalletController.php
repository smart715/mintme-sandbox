<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Fee;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
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

    public function __construct(
        TranslatorInterface $translations,
        UserActionLogger $userActionLogger,
        LockFactory $lockFactory,
        string $coinifySharedSecret
    ) {
        $this->translations = $translations;
        $this->userActionLogger = $userActionLogger;
        $this->lockFactory = $lockFactory;
        $this->coinifySharedSecret = $coinifySharedSecret;
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
     * @Rest\RequestParam(name="fee", allowBlank=false)
     * @Rest\RequestParam(
     *      name="address",
     *      allowBlank=false,
     *      requirements="^[a-zA-Z0-9]+$"
     *     )
     * @Rest\RequestParam(name="code", allowBlank=true)
     */
    public function withdraw(
        ParamFetcherInterface $request,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MoneyWrapperInterface $moneyWrapper,
        WalletInterface $wallet,
        MailerInterface $mailer
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

        $tradable = $cryptoManager->findBySymbol($request->get('crypto'))
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
                $tradable,
                new Fee($moneyWrapper->parse(
                    $request->get('fee'),
                    $tradable instanceof Token ? Symbols::TOK : $tradable->getSymbol()
                ))
            );
        } catch (Throwable $exception) {
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
            $mailer->sendWithdrawConfirmationMail($user, $pendingWithdraw);

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
    public function getDepositAddressesSignature(
        WalletInterface $depositCommunicator,
        CryptoManagerInterface $cryptoManager
    ): View {
        $this->denyAccessUnlessGranted('deposit');

        /** @var User $user*/
        $user = $this->getUser();

        $allCrypto = $cryptoManager->findAll();
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
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ): View {
        $this->denyAccessUnlessGranted('deposit');

        /** @var TradebleInterface|null $tradable */
        $tradable = $cryptoManager->findBySymbol($crypto) ?? $tokenManager->findByName($crypto);

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
}
