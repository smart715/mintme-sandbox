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
        $pendingOrders = $this->mapPendingOrdersByUser($this->getAllPendingOrders($market, $side));

        $usersOfPendingOrders = $this->userManager->findByIds(array_keys($pendingOrders));

        return array_map(function (User $userOfPendingOrder) use ($pendingOrders, $currentUser) {
            $orderUserId = $userOfPendingOrder->getId();
            $order = $pendingOrders[$orderUserId];
            $amount = floatval($order->getAmount());
            $price = floatval($order->getPrice());

            return [
                'firstName' => $userOfPendingOrder->getProfile()->getFirstName(),
                'lastName' => $userOfPendingOrder->getProfile()->getLastName(),
                'profileUrl' => $userOfPendingOrder->getProfile()->getPageUrl(),
                'amount' => $amount,
                'price' => $price,
                'total' => $price * $amount,
                'isOwner' => $currentUser && $orderUserId === $currentUser->getId(),
            ];
        }, $usersOfPendingOrders);
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
        } while (0 === count($pendingOrders));

        return $allPendingOrders;
    }

    private function mapPendingOrdersByUser(array $orders): array
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
