<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebSocketAPIController
{
    /** @var TraderInterface */
    private $trader;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private  $tokenManager;

    public function __construct(TraderInterface $trader, CryptoManagerInterface $cryptoManager, TokenManagerInterface $tokenManager)
    {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    public function authUser(Request $request, LoggerInterface $logger, ProfileManagerInterface $profileManager): JsonResponse
    {
        $logger->alert((string)json_encode($request->headers->all()));
        $token = $request->headers->get('authorization');
        $user = $profileManager->validateUserApi($token);
        return $user
            ? $this->confirmed($user)
            : $this->error();
    }

    public function cancelOrder($userid, $market, $orderid): JsonResponse
    {
        $crypto = $this->cryptoManager->findBySymbol('WEB');
        $token = $this->tokenManager->findByName($market);
        dump($crypto);
        $market = new Market($crypto, $token);
//        dump(json_encode($market));
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

        return new JsonResponse([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ]);
    }

    private function error(): JsonResponse
    {
        return new JsonResponse(
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

    private function confirmed(User $user): JsonResponse
    {
        return new JsonResponse(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => $user->getId()],
            ]
        );
    }
}
