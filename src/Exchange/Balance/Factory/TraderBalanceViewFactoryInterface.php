<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\TradebleInterface;
use App\Exchange\Balance\BalanceHandlerInterface;

interface TraderBalanceViewFactoryInterface
{
    /**
     * @param BalanceHandlerInterface $balanceHandler
     * @param array $traderBalances
     * @param TradebleInterface $tradable
     * @param int $limit
     * @param int $extend
     * @param int $incrementer
     * @param int $max
     * @return TraderBalanceView[]
     */
    public function create(
        BalanceHandlerInterface $balanceHandler,
        array $traderBalances,
        TradebleInterface $tradable,
        int $limit,
        int $extend,
        int $incrementer,
        int $max
    ): array;
}
