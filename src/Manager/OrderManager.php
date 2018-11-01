<?php

namespace App\Manager;

use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Exchange\Order;
use App\Fetcher\OrdersUserFetcher;

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
                $this->fetchOrdersUsers($pendingOrders)->fetchMakerIds()
            )
        );

        return array_map(function (Order $pendingOrder) use ($pendingOrdersUsers, $currentUser) {
            $orderMakerId = $pendingOrder->getMakerId();
            $amount = floatval($pendingOrder->getAmount());
            $price = floatval($pendingOrder->getPrice());
            $user = $pendingOrdersUsers[$orderMakerId];

            return [
                'firstName' => $user->getProfile()->getFirstName(),
                'lastName' => $user->getProfile()->getLastName(),
                'profileUrl' => $user->getProfile()->getPageUrl(),
                'amount' => $amount,
                'price' => $price,
                'total' => $price * $amount,
                'isOwner' => $currentUser && $orderMakerId === $currentUser->getId(),
            ];
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
        } while (0 === count($pendingOrders));

        return $allPendingOrders;
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

    private function fetchOrdersUsers(array $orders): OrdersUserFetcher
    {
        return new OrdersUserFetcher($orders);
    }
}
