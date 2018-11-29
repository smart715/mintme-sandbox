<?php

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpFoundation\Response;

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
     *  @Rest\Get("/cancel/{market}/{orderid}", name="order_cancel")
     *  @Rest\View()
     */
    public function cancelOrder(String $market, int $orderid): View
    {
        $crypto = $this->cryptoManager->findBySymbol($this->marketParser->parseSymbol($market));
        $token = $this->tokenManager->findByName($this->marketParser->parseName($market));
        if (null !== $token && null !== $crypto) {
            $market = new Market($crypto, $token);
            $order = new Order(
                $orderid,
                $this->getUser()->getId(),
                null,
                $market,
                new Money('0', new Currency($crypto->getSymbol())),
                1,
                new Money('0', new Currency($crypto->getSymbol())),
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

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/place-order", name="token_place_order")
     * @Rest\RequestParam(name="tokenName", allowBlank=false)
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="action", allowBlank=false)
     */
    public function placeOrder(
        ParamFetcherInterface $request,
        TraderInterface $trader,
        MarketManagerInterface $marketManager,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        $token = $this->tokenManager->findByName($request->get('tokenName'));
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        if (null === $token || null === $crypto) {
            throw $this->createNotFoundException('Token or Crypto not found.');
        }

        $market = $marketManager->getMarket($crypto, $token);

        if (null === $market) {
            throw $this->createNotFoundException('Market not found.');
        }

        $order = new Order(
            null,
            $this->getUser()->getId(),
            null,
            $market,
            $moneyWrapper->parse(
                $request->get('amountInput'),
                MoneyWrapper::TOK_SYMBOL
            ),
            Order::SIDE_MAP[$request->get('action')],
            $moneyWrapper->parse(
                $request->get('priceInput'),
                $crypto->getSymbol()
            ),
            Order::PENDING_STATUS
        );

        $tradeResult = $trader->placeOrder($order);

        return $this->view(
            [
                'result' => $tradeResult->getResult(),
                'message' => $tradeResult->getMessage(),
            ],
            Response::HTTP_ACCEPTED
        );
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
