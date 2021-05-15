<?php declare(strict_types = 1);

namespace App\Exchange\Market\Model;

use Money\Money;

class BuyOrdersSummaryResult
{
    private string $basePrice;
    private string $quoteAmount;

    public function __construct(string $basePrice, string $quoteAmount)
    {
        $this->basePrice = $basePrice;
        $this->quoteAmount = $quoteAmount;
    }

    public function getBasePrice(): string
    {
        return $this->basePrice;
    }

    public function getQuoteAmount(): string
    {
        return $this->quoteAmount;
    }
}
