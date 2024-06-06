<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\Exception\FetchException;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Logger\UserActionLogger;
use App\Manager\DeployNotificationManagerInterface;
use App\Manager\MarketStatusManager;
use App\Manager\TokenManagerInterface;
use App\Security\DisabledServicesVoter;
use App\Security\TradingVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Utils\Validator\MaxAllowedOrdersValidator;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/orders")
 */
class OrdersController extends AbstractFOSRestController
{
    private const LIMIT_EXECUTED_ORDERS = 50;
    private const PENDING_OFFSET = 100;
    private const WALLET_ITEMS_BATCH_SIZE = 11;

    private MarketHandlerInterface $marketHandler;
    private MarketFactoryInterface $marketManager;
    private UserActionLogger $userActionLogger;
    private TranslatorInterface $translations;
    private LockFactory $lockFactory;
    protected SessionInterface $session;
    private TokenManagerInterface $tokenManager;
    private LoggerInterface $logger;
    private DeployNotificationManagerInterface $deployNotificationManager;

    use ViewOnlyTrait;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketManager,
        UserActionLogger $userActionLogger,
        TranslatorInterface $translations,
        LockFactory $lockFactory,
        SessionInterface $session,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger,
        DeployNotificationManagerInterface $deployNotificationManager
    ) {
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
        $this->userActionLogger = $userActionLogger;
        $this->translations = $translations;
        $this->lockFactory = $lockFactory;
        $this->session = $session;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
        $this->deployNotificationManager = $deployNotificationManager;
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
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->denyAccessUnlessGranted(DisabledServicesVoter::NEW_TRADES);
        $this->denyAccessUnlessGranted(DisabledServicesVoter::TRADING);

        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted('not-blocked', $market->getQuote());
        $this->denyAccessUnlessGranted('operate', $market);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $lock = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$currentUser->getId());
        $orderDelay = (int)$this->getParameter('order_delay');
        $lockOrder = $this->lockFactory->createLock(
            LockFactory::LOCK_ORDER.$currentUser->getId(),
            $orderDelay,
            false
        );

        if (!$lock->acquire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$lockOrder->acquire()) {
            return $this->view([
                'error' =>  $this->translations->trans('cancel_order.delay', ['%seconds%' => $orderDelay]),
            ]);
        }

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
        }

        $lock->release();

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
        BalanceHandlerInterface $balanceHandler,
        ExchangerInterface $exchanger
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(DisabledServicesVoter::NEW_TRADES);
        $this->denyAccessUnlessGranted(DisabledServicesVoter::TRADING);
        $this->denyAccessUnlessGranted('operate', $market);

        if (!$this->isGranted(TradingVoter::ALL_ORDERS_ENABLED, $market)) {
            /** @var Token $token */
            $token = $market->getQuote();

            return $this->view(
                [
                    'message' => 'token.not_deployed_response',
                    'notified' => $this->deployNotificationManager->alreadyNotified($currentUser, $token),
                ],
                Response::HTTP_OK
            );
        }

        if (!$this->isGranted('trades-enabled', $market)) {
            return $this->view([
                'message' => $this->translations->trans('trading_disabled'),
            ], Response::HTTP_OK);
        }

        if (!$this->isGranted('make-order', $market)
            || (Order::SELL_SIDE === Order::SIDE_MAP[$request->get('action')]
                && !$this->isGranted('sell-order', $market))) {
             return $this->view(['error' => true, 'type' => 'action'], Response::HTTP_OK);
        }

        if (!$this->isGranted('not-blocked', $market->getQuote())) {
            return $this->view(
                ['result' => 2, 'message' => $this->translations->trans('api.user.blocked')],
                Response::HTTP_OK,
            );
        }

        $lockBalance = $this->lockFactory->createLock(LockFactory::LOCK_BALANCE.$currentUser->getId());
        $orderDelay = (int)$this->getParameter('order_delay');
        $lockOrder = $this->lockFactory->createLock(
            LockFactory::LOCK_ORDER.$currentUser->getId(),
            $orderDelay,
            false
        );

        if (!$lockBalance->acquire()) {
            throw $this->createAccessDeniedException();
        }

        if (!$lockOrder->acquire()) {
            return $this->view([
                'result' => TradeResult::FAILED,
                'message' => $this->translations->trans('place_order.delay', ['%seconds%' => $orderDelay]),
            ], Response::HTTP_OK);
        }

        $priceInput = $moneyWrapper->parse((string)$request->get('priceInput'), Symbols::TOK);
        $maximum = $moneyWrapper->parse((string)99999999.9999, Symbols::TOK);

        $maxAllowedOrders = $this->getParameter('max_allowed_active_orders');
        $maxAllowedValidator = new MaxAllowedOrdersValidator(
            $maxAllowedOrders,
            $currentUser,
            $this->marketHandler,
            $this->marketManager
        );

        try {
            if (!$maxAllowedValidator->validate()) {
                return $this->view([
                    'result' => TradeResult::FAILED,
                    'message' => $this->translations->trans(
                        'api.orders.max_allowed_active_orders',
                        ['%maxAllowed%' => $maxAllowedOrders],
                    ),
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Fetch exception (token_place_order): ' . $e->getMessage());
        }

        try {
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
        } catch (\Throwable $e) {
            $this->logger->error('Exception (token_place_order): ' . $e->getMessage());

            throw $e;
        } finally {
            $lockBalance->release();
        }

        $balance = $balanceHandler->balance(
            $currentUser,
            $market->getQuote()
        );

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
            'balance' => $balance->getFullAvailable(),
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

        $totalSellOrders = $this->marketHandler->getSellOrdersSummary($market)->getQuoteAmount();
        $totalBuyOrders = $this->marketHandler->getBuyOrdersSummary($market)->getBasePrice();

        $buyDepth = $marketStatusManager->getOrCreateMarketStatus($market)->getBuyDepth();

        return [
            'sell' => $pendingSellOrders,
            'buy' => $pendingBuyOrders,
            'buyDepth' => $buyDepth,
            'totalSellOrders' => $totalSellOrders,
            'totalBuyOrders' => $totalBuyOrders,
        ];
    }

    /**
     * @Rest\Get(
     *     "/{quote}/pending/summary", name="token_sell_orders_summary", options={"expose"=true}
     * )
     * @Rest\View()
     * @return string
     * @throws ApiNotFoundException
     */
    public function getTokenSellOrdersSummary(string $quote): string
    {
        $token = $this->tokenManager->findByName($quote);

        if (!$token) {
            throw new ApiNotFoundException('Token not found');
        }

        $user = $token->getOwner();

        if (!$user) {
            throw new ApiNotFoundException('Owner not found');
        }

        return $this->marketHandler->getTokenSellOrdersSummary($token, $user);
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/executed/last/{id}", name="executed_orders", defaults={"id"=1}, options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedOrders(Market $market, int $id): array
    {
        return $this->marketHandler->getExecutedOrders($market, $id, self::LIMIT_EXECUTED_ORDERS);
    }

    /**
     * @Rest\Get(
     *     "/executed/page/{page}/{donations}/{fullDonations}",
     *     name="executed_user_orders",
     *     defaults={"page"=1,"donations"=0,"fullDonations"=0},
     *     options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedUserOrders(int $page, int $donations, int $fullDonations): array
    {
        /** @var User $user*/
        $user = $this->getUser();

        $markets = $this->marketManager->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets,
            ($page - 1) * self::WALLET_ITEMS_BATCH_SIZE,
            self::WALLET_ITEMS_BATCH_SIZE,
            false,
            $donations,
            $fullDonations
        );
    }

    /**
     * @Rest\Get("/{base}/{quote}/executed/{id}", name="executed_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedOrderDetails(Market $market, int $id): View
    {
        $order = $this->marketHandler->getExecutedOrder($market, $id, self::LIMIT_EXECUTED_ORDERS);

        if (!$order) {
            throw new ApiNotFoundException();
        }

        return $this->view($order);
    }

    /**
     * @Rest\Get("/{base}/{quote}/pending/{id}", name="pending_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getPendingOrderDetails(Market $market, int $id): View
    {
        $order = $this->marketHandler->getPendingOrder($market, $id);

        if (!$order) {
            throw new ApiNotFoundException();
        }

        return $this->view($order, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/pending/page/{page}", name="orders", defaults={"page"=1}, options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getPendingUserOrders(int $page): array
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->createUserRelated($user),
            ($page - 1) * self::WALLET_ITEMS_BATCH_SIZE,
            self::WALLET_ITEMS_BATCH_SIZE
        );
    }
}
