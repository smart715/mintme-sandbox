<?php declare(strict_types = 1);

namespace App\Manager\Model;

use Money\Money;

/** @codeCoverageIgnore */
class FinanceIncomeModel
{
    private string $cryptoSymbol;
    private string $startDate;
    private string $endDate;
    private string $startAmount;
    private string $endAmount;
    private string $income;
    private string $usdValue;

    public function __construct(
        string $cryptoSymbol,
        string $startDate,
        string $endDate,
        string $startAmount,
        string $endAmount,
        string $income,
        string $usdValue
    ) {
        $this->cryptoSymbol = $cryptoSymbol;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->startAmount = $startAmount;
        $this->endAmount = $endAmount;
        $this->income = $income;
        $this->usdValue = $usdValue;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getStartAmount(): string
    {
        return $this->startAmount;
    }

    public function getEndAmount(): string
    {
        return $this->endAmount;
    }

    public function getIncome(): string
    {
        return $this->income;
    }

    public function getUsdValue(): string
    {
        return $this->usdValue;
    }

    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }
}
