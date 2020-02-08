<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\TradebleInterface;
use App\Entity\UserTradebleInterface;
use App\Exchange\Balance\BalanceHandlerInterface;

interface TraderBalanceViewFactoryInterface
{
    /**
     * @param UserTradebleInterface[] $usersTokens
     * @param string[] $balances
     * @param int $limit
     * @return TraderBalanceView[]
     */
    public function create(array $usersTokens, array $balances, int $limit): array;
}
