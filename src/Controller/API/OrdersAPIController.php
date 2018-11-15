<?php

namespace App\Controller\API;

use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

/** @Rest\Route("/api/orders") */
class OrdersAPIController extends FOSRestController
{
    /** @var TraderInterface */
    private $trader;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MarketNameParserInterface */
    private $marketParser;

    public function __construct(
        TraderInterface $trader,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketNameParserInterface $marketParser
    ) {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketParser = $marketParser;
    }

    /**
     *  @Rest\Get("/cancel-order/{userid}/{market}/{orderid}")
     *  @Rest\View()
     */
    public function cancelOrder(int $userid, String $market, int $orderid): View
    {
        $crypto = $this->cryptoManager->findBySymbol($this->marketParser->parseSymbol($market));
        $token = $this->tokenManager->findByName($this->marketParser->parseName($market));
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
}
