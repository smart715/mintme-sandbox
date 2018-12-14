<?php

namespace App\Order;

use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Manager\UserManager;
use App\Order\Model\OrderInfo;
use App\Wallet\Money\MoneyWrapperInterface;

class OrderList implements OrderListInterface
{
    /** @var Market\MarketHandlerInterface */
    private $marketHandler;

    /** @var UserManager */
    private $userManager;

    private const SELL_SIDE = 'sell';
    private const BUY_SIDE = 'buy';

    public function __construct(
        Market\MarketHandlerInterface $marketHandler,
        UserManager $userManager
    ) {
        $this->marketHandler= $marketHandler;
        $this->userManager = $userManager;
    }

    /** {@inheritdoc} */
    public function getSellPendingOrdersList(?User $currentUser, Market $market): array
    {
        return $this->getPendingOrdersList($currentUser, $market, self::SELL_SIDE);
    }

    /** {@inheritdoc} */
    public function getBuyPendingOrdersList(?User $currentUser, Market $market): array
    {
        return $this->getPendingOrdersList($currentUser, $market, self::BUY_SIDE);
    }

    /** {@inheritdoc} */
    public function getPendingOrdersList(?User $currentUser, Market $market, string $side): array
    {
        $pendingOrders = $this->getAllPendingOrders($market, $side);

        $pendingOrdersUsers = $this->mapUsersById(
            $this->userManager->findByIds(
                $this->getMakerIds($pendingOrders)
            )
        );

        return array_map(function (Order $pendingOrder) use ($pendingOrdersUsers, $currentUser) {
            return new OrderInfo(
                $pendingOrder,
                $pendingOrdersUsers[$pendingOrder->getMakerId()],
                null,
                $currentUser
            );
        }, $pendingOrders);
    }

    /** {@inheritdoc} */
    public function getAllPendingOrders(Market $market, string $side): array
    {
        $allPendingOrders = [];
        $rows = 100;
        $step = 0;

        do {
            $side === 'sell'
                ? $pendingOrders = $this->marketHandler->getPendingSellOrders($market, $step * $rows, $rows)
                : $pendingOrders = $this->marketHandler->getPendingBuyOrders($market, $step * $rows, $rows);
            $allPendingOrders = array_merge($allPendingOrders, $pendingOrders);
            ++$step;
        } while ($rows === count($pendingOrders));

        return $allPendingOrders;
    }

    /** {@inheritdoc} */
    public function getOrdersHistory(Market $market, int $offset = 0, int $limit = 20): array
    {
        $executedOrders = $this->marketHandler->getExecutedOrders($market, $offset, $limit);

        $executedOrdersUsers = $this->mapUsersById(
            $this->userManager->findByIds(
                $this->getUserIds($executedOrders)
            )
        );

        return array_map(function (Order $executedOrder) use ($executedOrdersUsers) {
            return new OrderInfo(
                $executedOrder,
                $executedOrdersUsers[$executedOrder->getMakerId()],
                $executedOrdersUsers[$executedOrder->getTakerId()],
                null
            );
        }, $executedOrders);
    }

    /** @param User[] $users */
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

    /** @param Order[] $orders */
    private function getMakerIds(array $orders): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getMakerId()) {
                    return $order->getMakerId();
                }
            }, $orders)
        );
    }

    /** @param Order[] $orders */
    private function getTakerIds(array $orders): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getTakerId()) {
                    return $order->getTakerId();
                }
            }, $orders)
        );
    }

    /** @param Order[] $orders */
    private function getUserIds(array $orders): array
    {
        return array_unique(
            array_merge($this->getMakerIds($orders), $this->getTakerIds($orders))
        );
    }
}
