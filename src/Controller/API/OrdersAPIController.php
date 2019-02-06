<?php

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Rest\Route("/api/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
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

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketManagerInterface */
    private $marketManager;

    /** @var NormalizerInterface */
    protected $normalizer;

    public function __construct(
        TraderInterface $trader,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketNameParserInterface $marketParser,
        MarketHandlerInterface $marketHandler,
        MarketManagerInterface $marketManager,
        NormalizerInterface $normalizer
    )
    {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketParser = $marketParser;
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
        $this->normalizer = $normalizer;
    }

    /**
     * @Rest\Get("/cancel/{market}/{orderid}", name="order_cancel", options={"expose"=true})
     * @Rest\View()
     */
    public function cancelOrder(String $market, int $orderid): View
    {
        $crypto = $this->cryptoManager->findBySymbol($this->marketParser->parseSymbol($market));
        $token = $this->tokenManager->findByHiddenName($this->marketParser->parseName($market));

        if (!$token || !$crypto) {
            throw new \InvalidArgumentException();
        }

        $market = new Market($crypto, $token);
        $order = new Order(
            $orderid,
            $this->getUser(),
            null,
            $market,
            new Money('0', new Currency($crypto->getSymbol())),
            1,
            new Money('0', new Currency($crypto->getSymbol())),
            ""
        );

        $tradeResult = $this->trader->cancelOrder($order);

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/place-order", name="token_place_order")
     * @Rest\RequestParam(name="tokenName", allowBlank=false)
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy|all)")
     */
    public function placeOrder(
        ParamFetcherInterface $request,
        TraderInterface $trader,
        MarketManagerInterface $marketManager,
        MoneyWrapperInterface $moneyWrapper
    ): View
    {
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
            $this->getUser(),
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
            Order::PENDING_STATUS,
            Order::SELL_SIDE === Order::SIDE_MAP[$request->get('action')]
                ? $this->getParameter('maker_fee_rate')
                : $this->getParameter('taker_fee_rate'),
            null,
            $this->getUser()->getReferrencer() ?
                $this->getUser()->getReferrencer()->getId() :
                0
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


    /**
     * @Rest\Get("/pending/{tokenName}", name="pending_orders", options={"expose"=true})
     * @Rest\View()
     */
    public function getPendingBuyOrder(String $tokenName): View
    {
        $market = $this->getMarket($tokenName);

        $pendingBuyOrders = $market
            ? ['buy' => $this->marketHandler->getPendingBuyOrders($market)]
            : [];

        $pendingSellOrders = $market
            ? ['sell' => $this->marketHandler->getPendingSellOrders($market)]
            : [];

        $orders = array_merge($pendingBuyOrders, $pendingSellOrders);
        return $this->view(
            $this->normalizer->normalize($orders, null, ['groups' => ['Default']])
        );
    }

    /**
     * @Rest\Get("/executed/{tokenName}", name="executed_orders", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedOrders(String $tokenName): View
    {
        $market = $this->getMarket($tokenName);

        $pendingBuyOrders = $market
            ? $this->marketHandler->getExecutedOrders($market)
            : [];

        return $this->view(
            $this->normalizer->normalize($pendingBuyOrders, null, ['groups' => ['Default']])
        );
    }

    /**
     * @Rest\Get("/executed", name="executed_user_orders", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedUserOrders(): View
    {
        $user = $this->getUser();

        $executedUserOrders =
            $this->marketHandler->getUserExecutedHistory(
                $user,
                $this->marketManager->getUserRelatedMarkets($user)
            );

        return $this->view(
            $this->normalizer->normalize($executedUserOrders, null, [
                'groups' => ['Default'],
            ])
        );
    }

    /**
     * @Rest\Get("/pending", name="orders", options={"expose"=true})
     * @Rest\View()
     */
    public function getUserOrders(): View
    {
        $user = $this->getUser();

        $orders = $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->getUserRelatedMarkets($user)
        );

        return $this->view(
            $this->normalizer->normalize($orders, null, [
                'groups' => ['Default'],
            ])
        );
    }

    private function getMarket(string $tokenName): ?Market
    {
        $token = $this->tokenManager->findByName($tokenName);
        $webCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        return $webCrypto && $token
            ? $this->marketManager->getMarket($webCrypto, $token)
            : null;
    }
}
