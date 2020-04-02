<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

interface BalancesArrayFactoryInterface
{
    /**
     * @param array[] $balances
     * @return string[]
     */
    public function create(array $balances): array;
}
