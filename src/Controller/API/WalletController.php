<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @Rest\Route("/api/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletController extends AbstractFOSRestController
{
    private const DEPOSIT_WITHDRAW_HISTORY_LIMIT = 100;

    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(UserActionLogger $userActionLogger)
    {
        $this->userActionLogger = $userActionLogger;
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
        return $wallet->getWithdrawDepositHistory(
            $this->getUser(),
            ($page - 1) * self::DEPOSIT_WITHDRAW_HISTORY_LIMIT,
            self::DEPOSIT_WITHDRAW_HISTORY_LIMIT
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/withdraw", name="withdraw", options={"2fa"="required"})
     * @Rest\RequestParam(name="crypto", allowBlank=false)
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(
     *      name="address",
     *      allowBlank=false,
     *      requirements="^[a-zA-Z0-9]+$"
     *     )
     * @Rest\RequestParam(name="code", allowBlank=false)
     */
    public function withdraw(
        ParamFetcherInterface $request,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MoneyWrapperInterface $moneyWrapper,
        WalletInterface $wallet,
        MailerInterface $mailer
    ): View {
        $tradable = $cryptoManager->findBySymbol($request->get('crypto'))
            ?? $tokenManager->findByName($request->get('crypto'));

        if (!$tradable) {
            return $this->view([
                'error' => 'Not found',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $pendingWithdraw = $wallet->withdrawInit(
                $this->getUser(),
                new Address(trim((string)$request->get('address'))),
                new Amount($moneyWrapper->parse(
                    $request->get('amount'),
                    $tradable instanceof Token ? MoneyWrapper::TOK_SYMBOL : $tradable->getSymbol()
                )),
                $tradable
            );
        } catch (Throwable $exception) {
            return $this->view([
                'error' => 'Withdrawal failed',
            ], Response::HTTP_BAD_GATEWAY);
        }

        $mailer->sendWithdrawConfirmationMail($this->getUser(), $pendingWithdraw);

        $this->userActionLogger->info("Sent withdrawal email for {$tradable->getSymbol()}", [
            'address' => $pendingWithdraw->getAddress()->getAddress(),
            'amount' => $pendingWithdraw->getAmount()->getAmount()->getAmount(),
        ]);

        return $this->view();
    }


    /**
     * @Rest\View()
     * @Rest\Get("/addresses", name="deposit_addresses", options={"expose"=true})
     */
    public function getDepositAddresses(
        WalletInterface $depositCommunicator,
        CryptoManagerInterface $cryptoManager
    ): View {
         $depositAddresses = $depositCommunicator->getDepositCredentials(
             $this->getUser(),
             $cryptoManager->findAll()
         );

        $tokenDepositAddress = $depositCommunicator->getTokenDepositCredentials($this->getUser());

        return $this->view(array_merge($depositAddresses, $tokenDepositAddress));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/deposit/{crypto}/fee", name="deposit_fee", options={"expose"=true})
     */
    public function getDepositFee(
        string $crypto,
        WalletInterface $depositCommunicator,
        CryptoManagerInterface $cryptoManager
    ): View {
        $crypto = $cryptoManager->findBySymbol($crypto);

        if (!$crypto) {
            return $this->view([
                'error' => 'Not found currency',
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        return $this->view($depositCommunicator->getFee($crypto));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/referral", name="referral_balance", options={"expose"=true})
     */
    public function getReferralBalance(
        BalanceHandlerInterface $balanceHandler,
        TokenManagerInterface $tokenManager
    ): View {
        $webToken = $tokenManager->findByName(Token::WEB_SYMBOL);

        if (!$webToken) {
            throw new InvalidArgumentException();
        }

        return $this->view([
            'balance' => $balanceHandler->balance($this->getUser(), $webToken)->getReferral(),
            'token' => $webToken,
        ]);
    }
}
