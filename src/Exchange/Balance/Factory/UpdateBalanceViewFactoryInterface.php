<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Balance\Model\UpdateBalanceResult;

interface UpdateBalanceViewFactoryInterface
{
    public function createUpdateBalanceView(
        UpdateBalanceResult $updateBalanceResult,
        string $symbol
    ): UpdateBalanceView;
}
