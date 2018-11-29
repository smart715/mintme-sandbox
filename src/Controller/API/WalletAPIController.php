<?php

namespace App\Controller\API;

use App\Manager\CryptoManagerInterface;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use App\Withdraw\WithdrawGatewayInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/** @Rest\Route("/api/wallet") */
class WalletAPIController extends FOSRestController
{
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
}
