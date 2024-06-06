<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResultContainer;

interface BalanceViewFactoryInterface
{
    /**
     * @param TradableInterface[] $tradablesProp
     * @param BalanceResultContainer $container
     * @param User $user
     * @return BalanceView[]
     */
    public function create(
        array $tradablesProp,
        BalanceResultContainer $container,
        User $user
    ): array;
}
