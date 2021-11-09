<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\UserTradebleInterface;

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
