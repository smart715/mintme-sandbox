<?php

namespace App\Manager;

use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Exchange\Order;

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

    public function getSellPendingOrdersList(?User $user, Market $market): array
    {
        return $this->getPendingOrdersList($user, $market, self::SELL_SIDE);
    }

    public function getBuyPendingOrdersList(?User $user, Market $market): array
    {
        return $this->getPendingOrdersList($user, $market, self::BUY_SIDE);
    }

    public function getPendingOrdersList(?User $user, Market $market, string $side): array
    {
        $pendingOrders = $this->mapOrdersByUserId($this->getAllPendingOrders($market, $side));

        $ordersUsers = $this->userManager->findByIds(array_keys($pendingOrders));

        return array_map(function (User $orderUser) use ($pendingOrders, $user) {
            $orderUserId = $orderUser->getId();
            $userOrder = $pendingOrders[$orderUserId];
            $amount = floatval($userOrder->getAmount());
            $price = floatval($userOrder->getPrice());

            return [
                'firstName' => $userOrder->getProfile()->getFirstName(),
                'lastName' => $userOrder->getProfile()->getFirstName(),
                'amount' => $amount,
                'price' => $price,
                'total' => $price * $amount,
                'isOwner' => $user && $orderUserId === $user->getId(),
            ];
        }, $ordersUsers);
    }

    public function getAllPendingOrders(Market $market, string $side): array
    {
        $allPendingOrders = [];
        $rows = 100;
        $step = 1;

        while (true) {
            $pendingOrders = $this->marketFetcher->getPendingOrders($market, --$step * $rows, $rows, $side);
            if (0 === count($pendingOrders)) {
                return $allPendingOrders;
            }
            $allPendingOrders = array_merge($allPendingOrders, $pendingOrders);
        }
    }

    private function mapOrdersByUserId(array $orders): array
    {
        return array_column(
            array_map(function (Order $order) {
                return [
                    'userId' => $order->getMakerId(),
                    'order' => $order,
                ];
            }, $orders),
            'order',
            'userId'
        );
    }
}
