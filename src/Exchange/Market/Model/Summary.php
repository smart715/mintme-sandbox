<?php declare(strict_types = 1);

namespace App\Exchange\Market\Model;

use Money\Money;

/** @codeCoverageIgnore */
class Summary
{
    private int $askCount;
    private Money $askAmount;
    private int $bidCount;
    private Money $bidAmount;

    public function __construct(
        int $askCount,
        Money $askAmount,
        int $bidCount,
        Money $bidAmount
    ) {
        $this->askCount = $askCount;
        $this->askAmount = $askAmount;
        $this->bidCount = $bidCount;
        $this->bidAmount = $bidAmount;
    }

    public function getAskCount(): int
    {
        return $this->askCount;
    }

    public function getAskAmount(): Money
    {
        return $this->askAmount;
    }

    public function getBidCount(): int
    {
        return $this->bidCount;
    }

    public function getBidAmount(): Money
    {
        return $this->bidAmount;
    }
}
