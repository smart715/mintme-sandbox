<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;

interface TraderBalanceViewFactoryInterface
{
    /**
     * @param BalanceHandlerInterface $balanceHandler
     * @param array $traderBalances
     * @param Token $token
     * @param int $limit
     * @param int $extend
     * @param int $incrementer
     * @return TraderBalanceView[]
     */
    public function create(
        BalanceHandlerInterface $balanceHandler,
        array $traderBalances,
        Token $token,
        int $limit,
        int $extend,
        int $incrementer
    ): array;
}
