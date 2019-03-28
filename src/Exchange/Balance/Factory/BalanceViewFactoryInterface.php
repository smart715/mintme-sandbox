<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Balance\Model\BalanceResultContainer;

interface BalanceViewFactoryInterface
{
    /** @return array<BalanceView> */
    public function create(BalanceResultContainer $container): array;
}
