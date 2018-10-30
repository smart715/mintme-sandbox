<?php

namespace App\Controller\API;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;

/** @Rest\Route("/api/ws") */
class WebSocketAPIController extends FOSRestController
{
    /** @var TraderInterface */
    private $trader;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(TraderInterface $trader, CryptoManagerInterface $cryptoManager, TokenManagerInterface $tokenManager)
    {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     *  @Rest\Get("/auth")
     *  @Rest\View()
     */
    public function authUser(Request $request, ProfileManagerInterface $profileManager): View
    {
        $token = $request->headers->get('authorization');
        if (null != $token && !is_array($token)) {
            $user = $profileManager->findProfileByHash($token);
            return $user
                ? $this->confirmed($user)
                : $this->error();
        } else {
            return $this->error();
        }
    }


    /**
     *  @Rest\Get("/cancel-order/{userid}/{market}/{orderid}")
     *  @Rest\View()
     */
    public function cancelOrder(int $userid, String $market, int $orderid): View
    {
        $crypto = $this->cryptoManager->findBySymbol(substr($market, -3));
        $token = $this->tokenManager->findByName(substr($market, 3, -3));
        if (null !== $token && null !== $crypto && null !== $userid) {
            $market = new Market($crypto, $token);
            $order = new Order(
                $orderid,
                $userid,
                null,
                $market,
                "",
                1,
                "",
                "",
                null
            );

            $tradeResult = $this->trader->cancelOrder($order);

            return $this->view([
                'result' => $tradeResult->getResult(),
                'message' => $tradeResult->getMessage(),
            ]);
        }
        return $this->error();
    }

    private function error(): View
    {
        return $this->view(
            [
                "error" =>
                    [
                        "code" => 5,
                        "message" => "service timeout",
                    ],
                "result" => null,
                "id" => 0,
            ]
        );
    }

    private function confirmed(User $user): View
    {
        return $this->view(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => $user->getId()],
            ]
        );
    }
}
