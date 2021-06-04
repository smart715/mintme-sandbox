<?php declare(strict_types = 1);

namespace App\Exchange;
use Money\Money;

/** @codeCoverageIgnore */
class CheckTradeResult
{
    /** @var string */
    private $expectedAmount;

    /** @var string */
    private $worth;

    public function __construct(Money $expectedAmount, Money $worth = null)
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
