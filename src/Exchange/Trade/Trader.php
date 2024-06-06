<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenInitOrder;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserCrypto;
use App\Events\OrderEvent;
use App\Exchange\AbstractOrder;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\OrderFilterConfig;
use App\Manager\UserManagerInterface;
use App\Repository\TokenInitOrderRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Trader implements TraderInterface
{
    private TraderFetcherInterface $fetcher;

    private LimitOrderConfig $config;

    private EntityManagerInterface $entityManager;

    private MoneyWrapperInterface $moneyWrapper;

    private MarketNameConverterInterface $marketNameConverter;

    private LoggerInterface $logger;

    private NormalizerInterface $normalizer;

    private float $referralFee;

    private EventDispatcherInterface $eventDispatcher;

    private BalanceHandlerInterface $balanceHandler;

    private MarketHandlerInterface $marketHandler;

    private UserManagerInterface $userManager;

    private TokenInitOrderRepository $tokenInitOrderRepository;

    public function __construct(
        TraderFetcherInterface $fetcher,
        LimitOrderConfig $config,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        MarketNameConverterInterface $marketNameConverter,
        NormalizerInterface $normalizer,
        LoggerInterface $logger,
        float $referralFee,
        EventDispatcherInterface $eventDispatcher,
        BalanceHandlerInterface $balanceHandler,
        MarketHandlerInterface $marketHandler,
        UserManagerInterface $userManager,
        TokenInitOrderRepository $tokenInitOrderRepository
    ) {
        $this->fetcher = $fetcher;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketNameConverter = $marketNameConverter;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->referralFee = $referralFee;
        $this->eventDispatcher = $eventDispatcher;
        $this->balanceHandler = $balanceHandler;
        $this->marketHandler = $marketHandler;
        $this->userManager = $userManager;
        $this->tokenInitOrderRepository = $tokenInitOrderRepository;
    }

    public function placeOrder(
        Order $order,
        bool $updateTokenOrCrypto = true,
        bool $isInitOrderType = false
    ): TradeResult {
        $user = $order->getMaker();
        $market = $order->getMarket();

        $takerFee = $makerFee = $user->getTradingFee() ?? $this->config->getFeeRateByMarket($market);

        $marketName = $this->marketNameConverter->convert($market);
        $result = $this->fetcher->placeOrder(
            $order->getMaker()->getId(),
            $marketName,
            $order->getSide(),
            $this->moneyWrapper->format($order->getAmount()),
            $this->moneyWrapper->format($order->getPrice()),
            $takerFee,
            $makerFee,
            $order->getReferralId() ?: 0,
            $this->referralFee ? (string)$this->referralFee : '0'
        );

        if (PlaceOrderResult::SUCCESS === $result->getResult()) {
            if ($isInitOrderType) {
                $this->storeInitialOrder($result, $user, $marketName);
            }

            $left = $this->moneyWrapper->parse($result->getLeft(), $order->getAmount()->getCurrency()->getCode());
            $amount = $this->moneyWrapper->parse($result->getAmount(), $order->getAmount()->getCurrency()->getCode());

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new OrderEvent(
                    $order,
                    $left,
                    $amount
                ),
                OrderEvent::CREATED
            );

            $order->setId($result->getId());

            if ($left->lessThan($amount)) {
                $this->updateMatchedOrders($order);
                $this->updateTokenInitialOrdersRelation($order);
            }

            if ($updateTokenOrCrypto) {
                $this->updateUserTradableRelation($order);
            }
        }

        if (PlaceOrderResult::FAILED === $result->getResult()) {
            $this->logger->error(
                "Failed to place new order for user {$order->getMaker()->getEmail()}.
                Reason: {$result->getMessage()}",
                (array)$this->normalizer->normalize($result, null, [
                    'groups' => ['Default'],
                ])
            );
        }

        return $result;
    }

    public function executeOrder(Order $order, bool $updateTokenOrCrypto = true): TradeResult
    {
        $amount = Order::SELL_SIDE === $order->getSide() ?
            $order->getAmount() :
            $order->getPrice()
        ;

        $result = $this->fetcher->executeOrder(
            $order->getMaker()->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getSide(),
            $this->moneyWrapper->format($amount),
            $this->moneyWrapper->format($order->getFee()),
            $order->getReferralId() ?: 0,
            $this->referralFee ? (string)$this->referralFee : '0'
        );

        if (TradeResult::SUCCESS === $result->getResult()) {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(new OrderEvent($order), OrderEvent::CREATED);

            if ($updateTokenOrCrypto) {
                $this->updateUserTradableRelation($order);
            }

            $order->setId($result->getId());

            $this->updateMatchedOrders($order);
        } elseif (TradeResult::FAILED === $result->getResult()) {
            $this->logger->error(
                "Failed to execute order for user {$order->getMaker()->getEmail()}.
                Reason: {$result->getMessage()}",
                (array)$this->normalizer->normalize($result, null, [
                    'groups' => ['Default'],
                ])
            );
        }

        return $result;
    }

    public function cancelOrder(Order $order): TradeResult
    {
        $userMaker = $order->getMaker();

        $result = $this->fetcher->cancelOrder(
            $userMaker->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getId() ?? 0
        );

        if (TradeResult::FAILED === $result->getResult()) {
            $this->logger->error(
                "Failed to cancel order '{$order->getId()}' for user {$userMaker->getEmail()}.
                Reason: {$result->getMessage()}"
            );
        } else {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(new OrderEvent($order), OrderEvent::CANCELLED);
        }

        $this->updateUserTradableRelation($order);
        $this->updateTokenInitialOrdersRelation($order);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        $records = $this->fetcher->getFinishedOrders(
            $user->getId(),
            $this->marketNameConverter->convert($market),
            $options['start_time'],
            $options['end_time'],
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']]
        );

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::FINISHED_STATUS);
        }, $records);
    }

    /**
     * @inheritdoc
     */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        $records = $this->fetcher->getPendingOrders(
            $user->getId(),
            $this->marketNameConverter->convert($market),
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']]
        );

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::PENDING_STATUS);
        }, $records);
    }

    /**
     * @inheritdoc
     */
    public function getOrderDetails(AbstractOrder $order, int $offset = 0, int $limit = 100): array
    {
        $orderDetails = $this->fetcher->getOrderDetails($order->getId(), $offset, $limit);

        return $this->marketHandler->parseDealsSingleMarket($orderDetails, $order->getMarket());
    }

    public function getFinishedOrder(Deal $deal): ?Order
    {
        $rawOrder = $this->fetcher->getFinishedOrderDetails($deal->getDealOrderId());

        if (!$rawOrder) {
            return null;
        }

        return $this->createOrder(
            $rawOrder,
            $this->userManager->find($rawOrder['user']),
            $deal->getMarket(),
            Order::FINISHED_STATUS
        );
    }

    public function getPendingOrder(Deal $deal): ?Order
    {
        $rawOrder = $this->fetcher->getPendingOrderDetails(
            $this->marketNameConverter->convert($deal->getMarket()),
            $deal->getDealOrderId()
        );

        if (!$rawOrder) {
            return null;
        }

        return $this->createOrder(
            $rawOrder,
            $this->userManager->find($rawOrder['user']),
            $deal->getMarket(),
            Order::PENDING_STATUS
        );
    }

    private function updateUserTradableRelation(Order $order): void
    {
        $user = $order->getMaker();
        $quote = $order->getMarket()->getQuote();

        if ($quote instanceof Token) {
            $this->balanceHandler->updateUserTokenRelation($user, $quote);
            $referencer = $user->getReferencer();
            $shouldUpdateReferencer = $referencer && !$quote->isOwner($referencer->getProfile()->getTokens());

            if ($shouldUpdateReferencer) {
                $this->balanceHandler->updateUserTokenRelation($referencer, $quote, true);
            }
        } elseif ($quote instanceof Crypto) {
            $this->updateUserCrypto($user, $quote);
        }
    }

    private function updateUserCrypto(User $user, Crypto $crypto): void
    {
        if (!$user->containsUserCrypto($crypto)) {
            $userCrypto = new UserCrypto($user, $crypto);
            $user->addCrypto($userCrypto);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    private function createOrder(array $orderData, User $user, Market $market, string $status): Order
    {
        $timestamp = array_key_exists('mtime', $orderData)
            ? (int)$orderData['mtime']
            : (array_key_exists('ctime', $orderData)
                ? (int)$orderData['ctime']
                : null);

        return new Order(
            $orderData['id'],
            $user,
            null,
            $market,
            $this->moneyWrapper->parse(
                $orderData['amount'],
                $this->getSymbol($market->getQuote())
            ),
            $orderData['side'],
            $this->moneyWrapper->parse(
                $orderData['price'],
                $this->getSymbol($market->getQuote())
            ),
            $status,
            null,
            $timestamp
        );
    }

    private function updateMatchedOrders(AbstractOrder $order): void
    {
        $orderDeals = $this->getOrderDetails($order);

        foreach ($orderDeals as $deal) {
            $finishedOrder = $this->getFinishedOrder($deal);
            $order = $finishedOrder ?? $this->getPendingOrder($deal);

            if ($order) {
                $this->updateUserTradableRelation($order);
                $this->updateTokenInitialOrdersRelation($order);
            }
        }
    }

    private function getSymbol(TradableInterface $tradable): string
    {
        return $tradable instanceof Token
            ? Symbols::TOK
            : $tradable->getSymbol();
    }

    private function storeInitialOrder(TradeResult $orderResult, User $user, string $marketName): void
    {
        if (TradeResult::SUCCESS === $orderResult->getResult()) {
            $tokenInitOrders = new TokenInitOrder();
            $tokenInitOrders->setUser($user);
            $tokenInitOrders->setOrderId($orderResult->getId());
            $tokenInitOrders->setMarketName($marketName);
            $this->entityManager->persist($tokenInitOrders);
            $this->entityManager->flush();
        }
    }

    private function updateTokenInitialOrdersRelation(Order $order): void
    {
        $initialOrder = $this->tokenInitOrderRepository->findOneBy(['orderId' => $order->getId()]);

        if ($initialOrder) {
            $this->entityManager->remove($initialOrder);
            $this->entityManager->flush();
        }
    }
}
