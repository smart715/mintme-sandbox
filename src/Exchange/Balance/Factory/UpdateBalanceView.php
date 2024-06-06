<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use Money\Money;

/** @codeCoverageIgnore */
class UpdateBalanceView
{
    private Money $change;
    private Money $bonusChange;

    public function __construct(Money $change, ?Money $bonusChange = null)
    {
        $this->change = $change;
        $this->bonusChange = $bonusChange ?? new Money(0, $change->getCurrency());
    }

    public function getChange(): Money
    {
        return $this->change;
    }

    public function getBonusChange(): Money
    {
        return $this->bonusChange;
    }
}
