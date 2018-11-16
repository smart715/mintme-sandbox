<?php

namespace App\Manager;

use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Exchange\Order;
use App\Order\OrderInfo;
use App\Order\OrdersUsers;

class OrderManager implements OrderManagerInterface
{
    /** @var MarketFetcher */
    private $marketFetcher;

    /** @var UserManager */
    private $userManager;

    private const SELL_SIDE = 'sell';
    private const BUY_SIDE = 'buy';

    public function __construct(MarketFetcher $marketFetcher, UserManager $userManager)
    {
        $this->marketFetcher = $marketFetcher;
        $this->userManager = $userManager;
    }

    public function getSellPendingOrdersList(?User $currentUser, Market $market): array
    {
        return $this->getPendingOrdersList($currentUser, $market, self::SELL_SIDE);
    }

    public function getBuyPendingOrdersList(?User $currentUser, Market $market): array
    {
        return $this->getPendingOrdersList($currentUser, $market, self::BUY_SIDE);
    }

    public function getPendingOrdersList(?User $currentUser, Market $market, string $side): array
    {
        $pendingOrders = $this->getAllPendingOrders($market, $side);

        $pendingOrdersUsers = $this->mapUsersById(
            $this->userManager->findByIds(
                $this->ordersUsers($pendingOrders)->getMakerIds()
            )
        );

        return array_map(function (Order $pendingOrder) use ($pendingOrdersUsers, $currentUser) {
            return $this->orderInfo(
                $pendingOrder,
                $pendingOrdersUsers[$pendingOrder->getMakerId()],
                null,
                $currentUser
            );
        }, $pendingOrders);
    }

    public function getAllPendingOrders(Market $market, string $side): array
    {
        $allPendingOrders = [];
        $rows = 100;
        $step = 0;

        do {
            $pendingOrders = $this->marketFetcher->getPendingOrders($market, $step * $rows, $rows, $side);
            $allPendingOrders = array_merge($allPendingOrders, $pendingOrders);
            ++$step;
        } while ($rows === count($pendingOrders));

        return $allPendingOrders;
    }

    public function getOrdersHistory(Market $market, int $offset = 0, int $limit = 20): array
    {
        $executedOrders = $this->marketFetcher->getExecutedOrders($market, $offset, $limit);

        $executedOrdersUsers = $this->mapUsersById(
            $this->userManager->findByIds(
                $this->ordersUsers($executedOrders)->getAllIds()
            )
        );

        return array_map(function (Order $executedOrder) use ($executedOrdersUsers) {
            return $this->orderInfo(
                $executedOrder,
                $executedOrdersUsers[$executedOrder->getMakerId()],
                $executedOrdersUsers[$executedOrder->getTakerId()],
                null
            );
        }, $executedOrders);
    }

    private function mapUsersById(array $users): array
    {
        return array_column(
            array_map(function (User $user) {
                return [
                    'id' => $user->getId(),
                    'user' => $user,
                ];
            }, $users),
            'user',
            'id'
        );
    }

    private function orderInfo(Order $order, User $makerUser, ?User $takerUser, ?User $currentUser): OrderInfo
    {
        return new OrderInfo($order, $makerUser, $takerUser, $currentUser);
    }

    private function ordersUsers(array $orders): OrdersUsers
    {
        return new OrdersUsers($orders);
    }
}
