<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use Money\Money;

/** @codeCoverageIgnore */
class CheckTradeResult
{
    private Money $expectedAmount;

    private ?Money $worth;

    public function __construct(Money $expectedAmount, ?Money $worth = null)
    {
        $this->expectedAmount = $expectedAmount;
        $this->worth = $worth;
    }

    public function getExpectedAmount(): Money
    {
        return $this->expectedAmount;
    }

    public function getWorth(): ?Money
    {
        return $this->worth;
    }
}
