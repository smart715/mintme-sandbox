<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\User;
use App\Events\OrderEvent;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Logger\UserActionLogger;
use App\Manager\MarketStatusManager;
use App\Utils\Symbols;
use App\Utils\Validator\MaxAllowedOrdersValidator;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/orders")
 */
class OrdersController extends AbstractFOSRestController
{
    private const OFFSET = 100;
    private const PENDING_OFFSET = 100;
    private const WALLET_OFFSET = 20;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var TranslatorInterface */
    private $translations;

    /** @var CryptoRatesFetcherInterface */
    private $cryptoRatesFetcher;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketManager,
        UserActionLogger $userActionLogger,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translations,
        CryptoRatesFetcherInterface $cryptoRatesFetcher
    ) {
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
        $this->userActionLogger = $userActionLogger;
        $this->eventDispatcher = $eventDispatcher;
        $this->translations = $translations;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
    }

    /**
     * @Rest\Post("/cancel/{base}/{quote}", name="orders_сancel", options={"expose"=true})
     * @Rest\RequestParam(name="orderData", allowBlank=false, description="array of orders ids")
     * @Rest\View()
     */
    public function cancelOrders(
        Market $market,
        ParamFetcherInterface $request,
        ExchangerInterface $exchanger
    ): View {
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');

        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted('not-blocked', $market->getQuote());

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        foreach ($request->get('orderData') as $id) {
            $order = new Order(
                $id,
                $currentUser,
                null,
                $market,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                1,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                ""
            );

            $exchanger->cancelOrder($market, $order);
            $this->userActionLogger->info('Cancel order', ['id' => $order->getId()]);

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(new OrderEvent($order), OrderEvent::CANCELLED);
        }

        return $this->view(Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{base}/{quote}/place-order", name="token_place_order", options={"expose"=true})
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="marketPrice", default="0")
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy)")
     */
    public function placeOrder(
        Market $market,
        MoneyWrapperInterface $moneyWrapper,
        ParamFetcherInterface $request,
        ExchangerInterface $exchanger
    ): View {
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $priceInput = $moneyWrapper->parse((string)$request->get('priceInput'), Symbols::TOK);
        $maximum = $moneyWrapper->parse((string)99999999.9999, Symbols::TOK);

        $rates = $this->cryptoRatesFetcher->fetch();
dd($rates);
        $minimun = $moneyWrapper->convert(
            $moneyWrapper->parse((string)$this->getParameter('minimum_order_value'), Symbols::USD),
            new Currency(Symbols::WEB),
            new FixedExchange([
                Symbols::USD => [
                    Symbols::WEB => 1,
                ]
            ])

        );
        dd($minimun);

        $this->denyAccessUnlessGranted('not-blocked', $market->getQuote());

        $maxAllowedOrders = $this->getParameter('max_allowed_active_orders');
        $maxAllowedValidator = new MaxAllowedOrdersValidator(
            $maxAllowedOrders,
            $currentUser,
            $this->marketHandler,
            $this->marketManager
        );

        if (!$maxAllowedValidator->validate()) {
            return $this->view([
                'result' => TradeResult::FAILED,
                'message' => $this->translations->trans(
                    'api.orders.max_allowed_active_orders',
                    ['%maxAllowed%' => $maxAllowedOrders],
                ),
            ], Response::HTTP_OK);
        }

        if ($priceInput->greaterThanOrEqual($maximum)) {
            return $this->view([
                'result' => TradeResult::FAILED,
                'message' => 'Invalid price quantity',
            ], Response::HTTP_OK);
        }

        $tradeResult = $exchanger->placeOrder(
            $currentUser,
            $market,
            (string)$request->get('amountInput'),
            (string)$request->get('priceInput'),
            (bool)$request->get('marketPrice'),
            Order::SIDE_MAP[$request->get('action')]
        );

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/pending/page/{page}", name="pending_orders", defaults={"page"=1}, options={"expose"=true}
     * )
     * @Rest\View()
     * @return mixed[]
     */
    public function getPendingOrders(Market $market, MarketStatusManager $marketStatusManager, int $page): array
    {
        $pendingBuyOrders = $this->marketHandler->getPendingBuyOrders(
            $market,
            ($page - 1) * self::PENDING_OFFSET,
            self::PENDING_OFFSET
        );

        $pendingSellOrders = $this->marketHandler->getPendingSellOrders(
            $market,
            ($page - 1) * self::PENDING_OFFSET,
            self::PENDING_OFFSET
        );

        $buyDepth = $marketStatusManager->getMarketStatus($market)->getBuyDepth();

        return [
            'sell' => $pendingSellOrders,
            'buy' => $pendingBuyOrders,
            'buyDepth' => $buyDepth,
        ];
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/executed/last/{id}", name="executed_orders", defaults={"id"=0}, options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedOrders(Market $market, int $id): array
    {
        return $this->marketHandler->getExecutedOrders($market, $id, self::OFFSET);
    }

    /**
     * @Rest\Get(
     *     "/executed/page/{page}/{donations}",
     *     name="executed_user_orders",
     *     defaults={"page"=1,"donations"=0},
     *     options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedUserOrders(int $page, int $donations): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user*/
        $user = $this->getUser();

        $markets = $this->marketManager->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets,
            ($page - 1) * self::WALLET_OFFSET,
            self::WALLET_OFFSET,
            false,
            $donations
        );
    }

    /**
     * @Rest\Get("/{base}/{quote}/executed/{id}", name="executed_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedOrderDetails(Market $market, int $id): View
    {
        return $this->view($this->marketHandler->getExecutedOrder($market, $id, self::OFFSET));
    }

    /**
     * @Rest\Get("/{base}/{quote}/pending/{id}", name="pending_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getPendingOrderDetails(Market $market, int $id): View
    {
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
}
