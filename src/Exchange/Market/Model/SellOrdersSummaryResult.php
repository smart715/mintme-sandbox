<?php declare(strict_types = 1);

namespace App\Exchange\Market\Model;

use Money\Money;

class SellOrdersSummaryResult
{
    private string $baseAmount;
    private string $quoteAmount;

    public function __construct(string $baseAmount, string $quoteAmount)
    {
        $this->baseAmount = $baseAmount;
        $this->quoteAmount = $quoteAmount;
    }

    public function getBaseAmount(): string
    {
        return $this->baseAmount;
    }

    public function getQuoteAmount(): string
    {
        return $this->quoteAmount;
    }
}
