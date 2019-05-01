<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersAPIController extends AbstractFOSRestController
{
    /** @var TraderInterface */
    private $trader;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketManager;

    public function __construct(
        TraderInterface $trader,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketManager
    ) {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
    }

    /**
     * @Rest\Post("/cancel/{base}/{quote}", name="orders_сancel", options={"expose"=true})
     * @Rest\RequestParam(name="orderData", allowBlank=false, description="array of orders ids")
     * @Rest\View()
     */
    public function cancelOrders(string $base, string $quote, ParamFetcherInterface $request): View
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new \InvalidArgumentException();
        }

        foreach ($request->get('orderData') as $id) {
            $order = new Order(
                $id,
                $this->getUser(),
                null,
                $market,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                1,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                ""
            );

            $this->trader->cancelOrder($order);
        }

        return $this->view(Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{base}/{quote}/place-order", name="token_place_order", options={"expose"=true})
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="marketPrice", default="0")
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy|all)")
     */
    public function placeOrder(
        string $base,
        string $quote,
        ParamFetcherInterface $request,
        TraderInterface $trader,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $market = $this->getMarket($base, $quote);

        if (null === $market) {
            throw $this->createNotFoundException('Market not found.');
        }

        $isSellSide = Order::SELL_SIDE === Order::SIDE_MAP[$request->get('action')];
        $price = $moneyWrapper->parse(
            $this->parseAmount($request->get('priceInput'), $market),
            $this->getSymbol($market->getQuote())
        );

        if ($request->get('marketPrice')) {
            /** @var Order[] $orders */
            $orders = $this->getPendingOrders($base, $quote)[$isSellSide ? 'buy' : 'sell'];

            if ($orders) {
                $price = $orders[0]->getPrice();
            }
        }

        $order = new Order(
            null,
            $this->getUser(),
            null,
            $market,
            $moneyWrapper->parse(
                $this->parseAmount($request->get('amountInput'), $market),
                $this->getSymbol($market->getQuote())
            ),
            Order::SIDE_MAP[$request->get('action')],
            $price,
            Order::PENDING_STATUS,
            $isSellSide ? $this->getParameter('maker_fee_rate') : $this->getParameter('taker_fee_rate'),
            null,
            $this->getUser()->getReferrencer() ? $this->getUser()->getReferrencer()->getId() : 0
        );

        $tradeResult = $trader->placeOrder($order);

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/{base}/{quote}/pending", name="pending_orders", options={"expose"=true})
     * @Rest\View()
     * @return mixed[]
     */
    public function getPendingOrders(string $base, string $quote): array
    {
        $market = $this->getMarket($base, $quote);

        $pendingBuyOrders = $market ? $this->marketHandler->getPendingBuyOrders($market) : [];
        $pendingSellOrders = $market ? $this->marketHandler->getPendingSellOrders($market) : [];

        return [
            'sell' => $pendingSellOrders,
            'buy' => $pendingBuyOrders,
        ];
    }

    /**
     * @Rest\Get("/{base}/{quote}/executed", name="executed_orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getExecutedOrders(string $base, string $quote): array
    {
        $market = $this->getMarket($base, $quote);

        return $market
            ? $this->marketHandler->getExecutedOrders($market)
            : [];
    }

    /**
     * @Rest\Get("/executed", name="executed_user_orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getExecutedUserOrders(): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->getUser();
        $markets = $this->marketManager->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets
        );
    }

    /**
     * @Rest\Get("/pending", name="orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getPendingUserOrders(): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->createUserRelated($user)
        );
    }

    private function parseAmount(string $amount, Market $market): string
    {
        /** @var Crypto $crypto */
        $crypto = $market->getQuote();

        return bcdiv($amount, '1', $market->isTokenMarket() ?
            $this->getParameter('token_precision') :
            $crypto->getShowSubunit());
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }

    private function getMarket(string $base, string $quote): ?Market
    {
        $base = $this->cryptoManager->findBySymbol($base) ?? $this->tokenManager->findByName($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        return ($base && $quote) && ($base !== $quote)
            ? $this->marketManager->create($base, $quote)
            : null;
    }
}
