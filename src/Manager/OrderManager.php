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

    public function getOrdersHistory(Market $market, int $offset = 0, int $limit = 20): array
    {
        $executedOrders = $this->marketFetcher->getExecutedOrders($market, $offset, $limit);

        $executedOrdersUsers = $this->mapUsersById(
            $this->userManager->findByIds(
                $this->fetchOrdersUsers($executedOrders)->fetchAllIds()
            )
        );

        return array_map(function (Order $executedOrder) use ($executedOrdersUsers) {
            $orderMakerId = $executedOrder->getMakerId();
            $orderTakerId = $executedOrder->getTakerId();
            $amount = floatval($executedOrder->getAmount());
            $price = floatval($executedOrder->getPrice());
            $makerUser = $executedOrdersUsers[$orderMakerId];
            $takerUser = $executedOrdersUsers[$orderTakerId];

            return [
                'maker' => [
                    'firstName' => $makerUser->getProfile()->getFirstName(),
                    'lastName' => $makerUser->getProfile()->getLastName(),
                    'profileUrl' => $makerUser->getProfile()->getPageUrl(),
                ],
                'taker' => [
                    'firstName' => $takerUser->getProfile()->getFirstName(),
                    'lastName' => $takerUser->getProfile()->getLastName(),
                    'profileUrl' => $takerUser->getProfile()->getPageUrl(),
                ],
                'amount' => $amount,
                'price' => $price,
                'total' => $price * $amount,
                'side' => $executedOrder->getSide(),
                'timestamp' => $executedOrder->getTimestamp(),
            ];
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

    private function fetchOrdersUsers(array $orders): OrdersUserFetcher
    {
        return new OrdersUserFetcher($orders);
    }
}
