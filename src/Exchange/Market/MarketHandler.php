<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Market\Model\LineStat;
use App\Exchange\MarketInfo;
use App\Exchange\Order;
use App\Manager\UserManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Exception;
use InvalidArgumentException;
use Money\Money;

class MarketHandler implements MarketHandlerInterface
{
    public const SELL = 1;
    public const BUY = 2;

    private const MONTH_PERIOD = 2592000;

    /** @var MarketFetcherInterface */
    private $marketFetcher;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        MoneyWrapperInterface $moneyWrapper,
        UserManagerInterface $userManager,
        MarketNameConverterInterface $marketNameConverter
    ) {
        $this->marketFetcher = $marketFetcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->userManager = $userManager;
        $this->marketNameConverter = $marketNameConverter;
    }

    public function getExecutedOrder(Market $market, int $id, int $limit = 100): Order
    {
        $orders = $this->getExecutedOrders($market, 0, $limit);

        foreach ($orders as $order) {
            if ($order->getId() === $id) {
                return $order;
            }
        }

        throw new Exception("Order not found");
    }

    public function getPendingOrder(Market $market, int $id): Order
    {
        $order = $this->marketFetcher->getPendingOrder(
            $this->marketNameConverter->convert($market),
            $id
        );

        return $this->parsePendingOrders([$order], $market)[0];
    }

    /** {@inheritdoc} */
    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parsePendingOrders(
            $this->marketFetcher->getPendingOrders(
                $this->marketNameConverter->convert($market),
                $offset,
                $limit,
                self::SELL
            ),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getPendingBuyOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parsePendingOrders(
            $this->marketFetcher->getPendingOrders(
                $this->marketNameConverter->convert($market),
                $offset,
                $limit,
                self::BUY
            ),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getExecutedOrders(Market $market, int $lastId = 0, int $limit = 100): array
    {
        return $this->parseExecutedOrders(
            $this->marketFetcher->getExecutedOrders(
                $this->marketNameConverter->convert($market),
                $lastId,
                $limit
            ),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getUserExecutedHistory(User $user, array $markets, int $offset = 0, int $limit = 100): array
    {
        $marketDeals = array_map(function (Market $market) use ($user, $offset, $limit) {
            return $this->parseDeals(
                $this->marketFetcher->getUserExecutedHistory(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $limit
                ),
                $market
            );
        }, $markets);

        $deals = $marketDeals ? array_merge(...$marketDeals) : [];

        uasort($deals, function (Deal $lDeal, Deal $rDeal) {
            return $lDeal->getTimestamp() > $rDeal->getTimestamp();
        });

        return $deals;
    }

    /** {@inheritdoc} */
    public function getPendingOrdersByUser(User $user, array $markets, int $offset = 0, int $limit = 100): array
    {
        $marketOrders = array_map(function (Market $market) use ($user, $offset, $limit) {
            return $this->parsePendingOrders(
                $this->marketFetcher->getPendingOrdersByUser(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $limit
                ),
                $market
            );
        }, $markets);

        $orders = $marketOrders ? array_merge(...$marketOrders) : [];

        uasort($orders, function (Order $lOrder, Order $rOrder) {
            return $lOrder->getTimestamp() > $rOrder->getTimestamp();
        });

        return $orders;
    }

    /** {@inheritdoc} */
    public function getKLineStatDaily(Market $market): array
    {
        $stats = $this->marketFetcher->getKLineStat(
            $this->marketNameConverter->convert($market),
            (new \DateTimeImmutable('1970-01-01 12:00:00'))->getTimestamp(),
            (new \DateTimeImmutable())->getTimestamp(),
            60 * 60 * 24
        );

        return array_map(function (array $line) use ($market) {
            return new LineStat(
                (new \DateTimeImmutable())->setTimestamp($line[0]),
                $this->moneyWrapper->parse($line[1], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[2], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[3], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[4], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[5], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[6], $this->getSymbol($market->getQuote())),
                $market
            );
        }, $stats);
    }

    /** {@inheritdoc} */
    public function getMarketStatus(Market $market, int $period = 86400): array
    {
        return $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            $period
        );
    }

    /** @return Order[] */
    private function parsePendingOrders(array $result, Market $market): array
    {
        $orders = array_key_exists('orders', $result)
            ? $result['orders']
            : $result;

        $filtered = [];

        array_walk($orders, function (array $orderData) use ($market, &$filtered): void {
            $user = $this->userManager->find($orderData['user']);

            if (!$user) {
                return;
            }

            $filtered[] = new Order(
                $orderData['id'],
                $user,
                null,
                $market,
                $this->moneyWrapper->parse(
                    (string)$orderData['left'],
                    $this->getSymbol($market->getQuote())
                ),
                $orderData['side'],
                $this->moneyWrapper->parse(
                    (string)$orderData['price'],
                    $this->getSymbol($market->getQuote())
                ),
                Order::PENDING_STATUS,
                $this->moneyWrapper->parse(
                    (string)$orderData['maker_fee'],
                    $this->getSymbol($market->getQuote())
                ),
                !empty($orderData['mtime']) ? intval($orderData['mtime']) : null,
                !empty($orderData['ctime']) ? intval($orderData['ctime']) : null
            );
        });

        return $filtered;
    }

    /** @return Order[] */
    private function parseExecutedOrders(array $result, Market $market): array
    {
        /** @var Order[] */
        $orders = [];

        foreach ($result as $orderData) {
            $user = array_key_exists('maker_id', $orderData)
                ? $this->userManager->find($orderData['maker_id'])
                : null;

            if (!$user) {
                continue;
            }

            $orders[] = new Order(
                $orderData['id'],
                $user,
                !empty($orderData['taker_id'])
                    ? $this->userManager->find($orderData['taker_id'])
                    : null,
                $market,
                $this->moneyWrapper->parse(
                    $orderData['amount'],
                    $this->getSymbol($market->getQuote())
                ),
                Order::SIDE_MAP[$orderData['type']],
                $this->moneyWrapper->parse(
                    $orderData['price'],
                    $this->getSymbol($market->getQuote())
                ),
                Order::FINISHED_STATUS,
                $this->moneyWrapper->parse(
                    !empty($orderData['fee']) ? (string)$orderData['fee'] : '0',
                    $this->getSymbol($market->getQuote())
                ),
                isset($orderData['time']) ? intval($orderData['time']) : null
            );
        }

        return $orders;
    }

    /** @return Deal[] */
    private function parseDeals(array $result, Market $market): array
    {
        return array_map(function (array $dealData) use ($market) {
            return new Deal(
                $dealData['id'],
                (int)$dealData['time'],
                $dealData['user'],
                $dealData['side'],
                $dealData['role'],
                $this->moneyWrapper->parse(
                    $dealData['amount'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['price'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['deal'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['fee'],
                    $this->getSymbol($market->getQuote())
                ),
                $dealData['deal_order_id'],
                $market
            );
        }, $result);
    }

    public function getMarketInfo(Market $market, int $period = 86400): MarketInfo
    {
        $result = $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            $period
        );

        if (!$result) {
            throw new InvalidArgumentException();
        }

        $monthResult = $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            self::MONTH_PERIOD
        );

        $buyDepth = $this->getBuyDepth($market);

        $expires = $result['expires'] && $monthResult['expires']
            ? (new \DateTimeImmutable())->setTimestamp(min($result['expires'], $monthResult['expires']))
            : ($monthResult['expires'] ?? ($result['expires'] ?? null));

        return new MarketInfo(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol(),
            $this->moneyWrapper->parse(
                $result['last'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['volume'],
                $this->getSymbol($market->getQuote())
            ),
            $this->moneyWrapper->parse(
                $result['open'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['close'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['high'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['low'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['deal'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $monthResult['deal'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $buyDepth,
                $this->getSymbol($market->getBase())
            ),
            $expires
        );
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }

    /**
     * Get market buy depth
     *
     * @param Market $market
     * @return string
     */
    public function getBuyDepth(Market $market): string
    {
        $offset = 0;
        $limit = 100;
        $paginatedOrders = [];

        do {
            $moreOrders = $this->getPendingBuyOrders($market, $offset, $limit);
            $paginatedOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedOrders);

        $zeroDepth = $this->moneyWrapper->parse(
            '0',
            $market->isTokenMarket() ? Token::TOK_SYMBOL : $market->getQuote()->getSymbol()
        );

        /** @var Money $depthAmount */
        $depthAmount = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getPrice()->multiply(
                $this->moneyWrapper->format($order->getAmount())
            )->add($sum);
        }, $zeroDepth);

        return $this->moneyWrapper->format($depthAmount);
    }

    public function getSellOrdersSummary(Market $market): string
    {
        $offset = 0;
        $limit = 100;
        $paginatedOrders = [];

        do {
            $moreOrders = $this->getPendingSellOrders($market, $offset, $limit);
            $paginatedOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedOrders);

        $zeroDepth = $this->moneyWrapper->parse('0', Token::TOK_SYMBOL);

        /** @var Money $sellOrdersSum */
        $sellOrdersSum = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getPrice()->multiply(
                $this->moneyWrapper->format($order->getAmount())
            )->add($sum);
        }, $zeroDepth);

        return $this->moneyWrapper->format($sellOrdersSum);
    }
}
