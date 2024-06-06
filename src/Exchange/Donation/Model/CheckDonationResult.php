<?php declare(strict_types = 1);

namespace App\Exchange\Donation\Model;

use Money\Money;

class CheckDonationResult
{
    private Money $expectedTokensAmount;
    private Money $expectedTokensWorth;

    public function __construct(Money $expectedTokensAmount, Money $expectedTokensWorth)
    {
        $this->expectedTokensAmount = $expectedTokensAmount;
        $this->expectedTokensWorth = $expectedTokensWorth;
    }

    public function getExpectedTokensAmount(): Money
    {
        return $this->expectedTokensAmount;
    }

    public function getExpectedTokensWorth(): Money
    {
        return $this->expectedTokensWorth;
    }
}
