<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\PendingWithdraw;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletAPIController extends AbstractFOSRestController
{
    private const DEPOSIT_WITHDRAW_HISTORY_LIMIT = 20;

    /**
     * @Rest\View()
     * @Rest\GET(
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
     * @Rest\Post("/withdraw", name="withdraw")
     * @Rest\RequestParam(name="crypto", allowBlank=false)
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(name="address", allowBlank=false)
     * @Rest\RequestParam(name="code")
     */
    public function withdraw(
        ParamFetcherInterface $request,
        CryptoManagerInterface $cryptoManager,
        TwoFactorManagerInterface $twoFactorManager,
        MoneyWrapperInterface $moneyWrapper,
        MailerInterface $mailer
    ): View {
        $user = $this->getUser();

        if (!$user) {
            return $this->view([
                'error' => 'Invalid user',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $crypto = $cryptoManager->findBySymbol(
            $request->get('crypto')
        );

        if (!$crypto) {
            return $this->view([
                'error' => 'Not found currency',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($user->isGoogleAuthenticatorEnabled() && !$twoFactorManager->checkCode($user, $request->get('code'))) {
            return $this->view([
                'error' => 'Invalid 2fa code',
                ], Response::HTTP_UNAUTHORIZED);
        }

        $pendingWithdraw = new PendingWithdraw(
            $user,
            $crypto,
            new Amount($moneyWrapper->parse($request->get('amount'), $crypto->getSymbol())),
            new Address(trim((string)$request->get('address')))
        );

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($pendingWithdraw);
        $entityManager->flush();

        $mailer->sendWithdrawConfirmationMail($user, $pendingWithdraw);

        return $this->view();
    }


    /**
     * @Rest\View()
     * @Rest\GET("/addresses", name="deposit_addresses", options={"expose"=true})
     */
    public function getDepositAddresses(
        WalletInterface $depositCommunicator,
        CryptoManagerInterface $cryptoManager
    ): View {

        $depositAddresses = $depositCommunicator->getDepositCredentials(
            $this->getUser(),
            $cryptoManager->findAll()
        );

        return $this->view($depositAddresses);
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
}
