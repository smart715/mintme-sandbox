<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Balance\Model\UpdateBalanceResult;
use App\Wallet\Money\MoneyWrapperInterface;

class UpdateBalanceViewFactory implements UpdateBalanceViewFactoryInterface
{
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(MoneyWrapperInterface $moneyWrapper)
    {
        $this->moneyWrapper = $moneyWrapper;
    }

    public function createUpdateBalanceView(
        UpdateBalanceResult $updateBalanceResult,
        string $symbol
    ): UpdateBalanceView {
        return new UpdateBalanceView(
            $this->moneyWrapper->parse(
                $updateBalanceResult->getChange(),
                $symbol
            )
        );
    }
}
