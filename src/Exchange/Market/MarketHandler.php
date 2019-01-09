<?php

namespace App\Exchange\Market;

use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Manager\UserManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class MarketHandler implements MarketHandlerInterface
{
    /** @var MarketFetcherInterface */
    private $marketFetcher;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var UserManagerInterface */
    protected $userManager;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        MoneyWrapperInterface $moneyWrapper,
        UserManagerInterface $userManager
    ) {
        $this->marketFetcher = $marketFetcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->userManager = $userManager;
    }

    /** {@inheritdoc} */
    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parsePendingOrders(
            $this->marketFetcher->getPendingOrders($market->getHiddenName(), $offset, $limit, MarketFetcher::SELL),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getPendingBuyOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parsePendingOrders(
            $this->marketFetcher->getPendingOrders($market->getHiddenName(), $offset, $limit, MarketFetcher::BUY),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getExecutedOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->parseExecutedOrders(
            $this->marketFetcher->getExecutedOrders($market->getHiddenName(), $offset, $limit),
            $market
        );
    }

    /** {@inheritdoc} */
    public function getUserExecutedHistory(User $user, array $markets, int $offset = 0, int $limit = 100): array
    {
        $marketDeals = array_map(function (Market $market) use ($user, $offset, $limit) {
            return $this->parseDeals(
                $this->marketFetcher->getUserExecutedHistory($user->getId(), $market->getHiddenName(), $offset, $limit),
                $market
            );
        }, $markets);

        $deals = array_merge(...$marketDeals);

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
                $this->marketFetcher->getPendingOrdersByUser($user->getId(), $market->getHiddenName(), $offset, $limit),
                $market
            );
        }, $markets);

        $orders = array_merge(...$marketOrders);

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
                    $orderData['amount'],
                    $market->getCurrencySymbol()
                ),
                $orderData['side'],
                $this->moneyWrapper->parse(
                    $orderData['price'],
                    $market->getCurrencySymbol()
                ),
                Order::PENDING_STATUS,
                $orderData['maker_fee'],
                $orderData['mtime']
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
                    $market->getCurrencySymbol()
                ),
                Order::SIDE_MAP[$orderData['type']],
                $this->moneyWrapper->parse(
                    $orderData['price'],
                    $market->getCurrencySymbol()
                ),
                Order::FINISHED_STATUS,
                array_key_exists('fee', $orderData) ? $orderData['fee'] : 0,
                $orderData['time']
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
                    $market->getCurrencySymbol()
                ),
                $this->moneyWrapper->parse(
                    $dealData['price'],
                    $market->getCurrencySymbol()
                ),
                $this->moneyWrapper->parse(
                    $dealData['deal'],
                    $market->getCurrencySymbol()
                ),
                $this->moneyWrapper->parse(
                    $dealData['fee'],
                    $market->getCurrencySymbol()
                ),
                $dealData['deal_order_id'],
                $market
            );
        }, $result['records']);
    }
}
