<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactory;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Validator\MinOrderValidator;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * @Rest\Route("/api/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersAPIController extends AbstractFOSRestController
{
    private const OFFSET = 100;
    private const PENDING_OFFSET = 100;
    private const WALLET_OFFSET = 20;

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

    /** @var LoggerInterface */
    private $logger;

    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(
        TraderInterface $trader,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketManager,
        LoggerInterface $logger,
        UserActionLogger $userActionLogger
    ) {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
        $this->logger = $logger;
        $this->userActionLogger = $userActionLogger;
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
            throw new InvalidArgumentException();
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
            $this->userActionLogger->info('Cancel order', ['id' => $order->getId()]);
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
        MoneyWrapperInterface $moneyWrapper,
        MarketAMQPInterface $marketProducer,
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactory $balanceViewFactory
    ): View {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $market = $this->getMarket($base, $quote);

        if (null === $market) {
            throw $this->createNotFoundException('Market not found.');
        }

        $isSellSide = Order::SELL_SIDE === Order::SIDE_MAP[$request->get('action')];

        if ($isSellSide && $this->exceedAvailableReleased(
            $quote,
            $request->get('amountInput'),
            $balanceHandler,
            $balanceViewFactory,
            $moneyWrapper
        )) {
            return $this->view([
                'result' => 3,
                'message' => (new TradeResult(TradeResult::INSUFFICIENT_BALANCE))->getMessage(),
            ], Response::HTTP_ACCEPTED);
        }

        if (!(new MinOrderValidator(
            $market->getBase(),
            $market->getQuote(),
            $request->get('priceInput'),
            $request->get('amountInput')
        ))->validate()) {
            return $this->view([
                'result' => TradeResult::SMALL_AMOUNT,
                'message' => (new TradeResult(TradeResult::SMALL_AMOUNT))->getMessage(),
            ], Response::HTTP_ACCEPTED);
        }

        $price = $moneyWrapper->parse(
            $this->parseAmount($request->get('priceInput'), $market),
            $this->getSymbol($market->getQuote())
        );

        if ($request->get('marketPrice')) {
            /** @var Order[] $orders */
            $orders = $this->getPendingOrders($base, $quote, 1)[$isSellSide ? 'buy' : 'sell'];

            if ($orders) {
                $price = $orders[0]->getPrice();
            }
        }

        $amount = $moneyWrapper->parse(
            $this->parseAmount($request->get('amountInput'), $market),
            $this->getSymbol($market->getQuote())
        );

        $order = new Order(
            null,
            $this->getUser(),
            null,
            $market,
            $amount,
            Order::SIDE_MAP[$request->get('action')],
            $price,
            Order::PENDING_STATUS,
            $isSellSide ? $this->getParameter('maker_fee_rate') : $this->getParameter('taker_fee_rate'),
            null,
            $this->getUser()->getReferrencer() ? (int)$this->getUser()->getReferrencer()->getId() : 0
        );

        $tradeResult = $trader->placeOrder($order);

        try {
            $marketProducer->send($market);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to update '${base}/${quote}' market status. Reason: {$exception->getMessage()}"
            );
        }

        $this->userActionLogger->info(sprintf('Create %s order', $request->get('action')), [
            'base' => $market->getBase()->getSymbol(),
            'quote' => $market->getQuote()->getSymbol(),
            'amount' => $amount->getAmount(),
            'price' => $price->getAmount(),
        ]);

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/pending/page/{page}", name="pending_orders", defaults={"page"=1}, options={"expose"=true}
     * )
     * @Rest\View()
     * @return mixed[]
     */
    public function getPendingOrders(string $base, string $quote, int $page): array
    {
        $market = $this->getMarket($base, $quote);

        $pendingBuyOrders = $market ?
            $this->marketHandler->getPendingBuyOrders(
                $market,
                ($page - 1) * self::PENDING_OFFSET,
                self::PENDING_OFFSET
            ) :
            [];
        $pendingSellOrders = $market ?
            $this->marketHandler->getPendingSellOrders(
                $market,
                ($page - 1) * self::PENDING_OFFSET,
                self::PENDING_OFFSET
            ) :
            [];

        return [
            'sell' => $pendingSellOrders,
            'buy' => $pendingBuyOrders,
        ];
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/executed/last/{id}", name="executed_orders", defaults={"id"=0}, options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedOrders(string $base, string $quote, int $id): array
    {
        $market = $this->getMarket($base, $quote);

        return $market
            ? $this->marketHandler->getExecutedOrders($market, $id, self::OFFSET)
            : [];
    }

    /**
     * @Rest\Get("/executed/page/{page}", name="executed_user_orders", defaults={"page"=1}, options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedUserOrders(int $page): array
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
            $markets,
            ($page - 1) * self::WALLET_OFFSET,
            self::WALLET_OFFSET
        );
    }

    /**
     * @Rest\Get("/{base}/{quote}/executed/{id}", name="executed_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedOrderDetails(string $base, string $quote, int $id): View
    {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new InvalidArgumentException();
        }

        return $this->view($this->marketHandler->getExecutedOrder($market, $id, self::OFFSET));
    }

    /**
     * @Rest\Get("/{base}/{quote}/pending/{id}", name="pending_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getPendingOrderDetails(string $base, string $quote, int $id): View
    {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new InvalidArgumentException();
        }

        return $this->view($this->marketHandler->getPendingOrder($market, $id));
    }

    /**
     * @Rest\Get("/pending/page/{page}", name="orders", defaults={"page"=1}, options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getPendingUserOrders(int $page): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->createUserRelated($user),
            ($page - 1) * self::WALLET_OFFSET,
            self::WALLET_OFFSET
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

    private function exceedAvailableReleased(
        string $quote,
        string $amount,
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactory $balanceViewFactory,
        MoneyWrapperInterface $moneyWrapper
    ): bool {
        $token = $this->tokenManager->findByName($quote);
        $profile = $token->getProfile();

        if ($profile && $this->getUser() === $profile->getUser()) {
            /** @var BalanceView $balanceViewer */
            $balanceViewer = $balanceViewFactory->create(
                $balanceHandler->balances($this->getUser(), [$token])
            )[$quote];

            return $moneyWrapper
                ->parse($amount, MoneyWrapper::TOK_SYMBOL)
                ->greaterThan($balanceViewer->getAvailable());
        }

        return false;
    }
}
