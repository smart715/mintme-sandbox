<?php

namespace App\Controller\API;

use App\Deposit\DepositGatewayCommunicatorInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use App\Withdraw\WithdrawGatewayInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletAPIController extends FOSRestController
{
    private const DEPOSIT_WITHDRAW_HISTORY_LIMIT = 5;

    /**
     * @Rest\View()
     * @Rest\GET("/history/{page}", name="api_history", requirements={"page"="^[1-9]\d*$"})
     */
    public function getDepositWithdrawHistory(
        int $page,
        ParamFetcherInterface $request,
        WalletInterface $wallet
    ): View {

        $depositWithdrawHistory = $wallet
            ->getWithdrawDepositHistory(
                $this->getUser(),
                $page - 1,
                self::DEPOSIT_WITHDRAW_HISTORY_LIMIT
            )
        ;

        return $this->view($depositWithdrawHistory);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/withdraw", name="withdraw")
     * @Rest\RequestParam(name="crypto", allowBlank=false)
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(name="address", allowBlank=false)
     */
    public function withdraw(
        ParamFetcherInterface $request,
        WalletInterface $wallet,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        $crypto = $cryptoManager->findBySymbol(
            $request->get('crypto')
        );

        if (!$crypto) {
            return $this->view([
                'error' => 'Not found currency',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $wallet->withdraw(
                $this->getUser(),
                new Address($request->get('address')),
                new Amount($moneyWrapper->parse($request->get('amount'), $crypto->getSymbol())),
                $crypto
            );
        } catch (NotEnoughUserAmountException $exception) {
            return $this->view([
                'error' => 'Not enough balance to withdraw',
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->view([
                'error' => 'Service unavailable now. Try later',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->view();
    }


    /**
     * @Rest\View()
     * @Rest\GET("/addresses", name="deposit_addresses")
     */
    public function getDepositAddresses(
        DepositGatewayCommunicatorInterface $depositCommunicator,
        TokenManagerInterface $tokenManager
    ): View {

        $depositAddresses = $depositCommunicator->getDepositCredentials(
            $this->getUser()->getId(),
            $tokenManager->findAllPredefined()
        )->toArray();

        return $this->view($depositAddresses);
    }

}
