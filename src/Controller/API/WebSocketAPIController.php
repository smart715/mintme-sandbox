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
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    /** @Rest\Route("/internal/exchange/user/auth/") */
    public function authUser(Request $request, ProfileManagerInterface $profileManager): JsonResponse
    {
        $token = $request->headers->get('authorization');
        if ($token != null && !is_array($token)) {
            $user = $profileManager->validateUserApi($token);
            return $user
                ? $this->confirmed($user)
                : $this->error();
        } else {
            return $this->error();
        }
    }

    /** @Rest\Route("/api/user/cancel-order/{userid}/{market}/{orderid}") */
    public function cancelOrder(int $userid, String $market, int $orderid): JsonResponse
    {
        $crypto = $this->cryptoManager->findBySymbol('WEB');
        $token = $this->tokenManager->findByName($market);
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

            return new JsonResponse([
                'result' => $tradeResult->getResult(),
                'message' => $tradeResult->getMessage(),
            ]);
        }
        return $this->error();
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
