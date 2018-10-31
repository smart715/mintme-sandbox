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

    public function getSellPendingOrdersList(User $user, Market $market): array
    {
        return $this->getPendingOrdersList($user, $market, self::SELL_SIDE);
    }

    public function getBuyPendingOrdersList(User $user, Market $market): array
    {
        return $this->getPendingOrdersList($user, $market, self::BUY_SIDE);
    }

    public function getPendingOrdersList(User $user, Market $market, string $side): array
    {
        $pendingOrders = $this->mapOrdersByUserId($this->getAllPendingOrders($market, $side));

        $users = $this->userManager->findByIds(array_keys($pendingOrders));

        return array_map(function (User $user) use ($pendingOrders) {
            $userId = $user->getId();
            $amount = floatval($pendingOrders[$userId]->getAmount());
            $price = floatval($pendingOrders[$userId]->getPrice());

            return [
                'firstName' => $user->getProfile()->getFirstName(),
                'lastName' => $user->getProfile()->getFirstName(),
                'amount' => $amount,
                'price' => $price,
                'total' => $price * $amount,
                'isOwner' => $userId === $user->getId(),
            ];
        }, $users);
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
