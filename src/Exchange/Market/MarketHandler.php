<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\MarketInfo;
use App\Exchange\Order;
use App\Manager\UserManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;

class MarketHandler implements MarketHandlerInterface
{
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

    /** {@inheritdoc} */
    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parsePendingOrders(
            $this->marketFetcher->getPendingOrders(
                $this->marketNameConverter->convert($market),
                $offset,
                $limit,
                MarketFetcher::SELL
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
                MarketFetcher::BUY
            ),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getExecutedOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parseExecutedOrders(
            $this->marketFetcher->getExecutedOrders(
                $this->marketNameConverter->convert($market),
                $offset,
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

    /** @return Order[] */
    private function parsePendingOrders(array $result, Market $market): array
    {
        $orders = array_key_exists('orders', $result)
            ? $result['orders']
            : $result;

        return array_map(function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                $this->userManager->find($orderData['user']),
                null,
                $market,
                $this->moneyWrapper->parse(
                    $orderData['left'],
                    $this->getSymbol($market->getQuote())
                ),
                $orderData['side'],
                $this->moneyWrapper->parse(
                    $orderData['price'],
                    $this->getSymbol($market->getQuote())
                ),
                Order::PENDING_STATUS,
                $orderData['maker_fee'] ? floatval($orderData['maker_fee']) : null,
                $orderData['mtime'] ? intval($orderData['mtime']) : null
            );
        }, $orders);
    }

    /** @return Order[] */
    private function parseExecutedOrders(array $result, Market $market): array
    {
        return array_map(function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                array_key_exists('maker_id', $orderData)
                    ? $this->userManager->find($orderData['maker_id'])
                    : null,
                array_key_exists('taker_id', $orderData)
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
                array_key_exists('fee', $orderData) ? $orderData['fee'] : 0,
                $orderData['time'] ? intval($orderData['time']) : null
            );
        }, $result);
    }

    /** @return Deal[] */
    private function parseDeals(array $result, Market $market): array
    {
        return array_map(function (array $dealData) use ($market) {
            return new Deal(
                $dealData['id'],
                $dealData['time'],
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

    /**
     * @param  Market[] $markets
     * @return MarketInfo[]
     */
    public function getMarketsInfo(array $markets): array
    {
        $marketsInfo = [];
        foreach ($markets as $market) {
            $result = $this->marketFetcher->getMarketInfo($this->marketNameConverter->convert($market));
            if (!$result) {
                break;
            }
            $marketsInfo[$this->marketNameConverter->convert($market)] = new MarketInfo(
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol(),
                $this->moneyWrapper->parse(
                    $result['last'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $result['volume'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $result['open'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $result['close'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $result['high'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $result['low'],
                    $this->getSymbol($market->getQuote())
                ),
                $result['deal']
            );
        }
        return $marketsInfo;
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
