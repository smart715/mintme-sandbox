<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
class ProfitBotModel extends AbstractProfitModel
{
    private string $profit;
    private string $totalSold;
    private string $totalBought;
    private string $sellCount;
    private string $buyCount;
    private string $quote;
    private string $base;

    public function __construct(
        string $symbol,
        string $base = '',
        string $quote = '',
        string $totalSold = '0',
        string $totalBought = '0',
        string $profit = '0',
        string $profitInUsd = '0',
        string $sellCount = '0',
        string $buyCount = '0',
        string $count = '0'
    ) {
        parent::__construct($symbol, $profitInUsd, $count);

        $this->quote = $quote;
        $this->base = $base;
        $this->totalSold = $totalSold;
        $this->totalBought = $totalBought;
        $this->sellCount = $sellCount;
        $this->buyCount = $buyCount;
        $this->profit = $profit;
    }

    public function getProfit(): string
    {
        return $this->profit;
    }

    public function getTotalSold(): string
    {
        return $this->totalSold;
    }

    public function getTotalBought(): string
    {
        return $this->totalBought;
    }

    public function getSellCount(): string
    {
        return $this->sellCount;
    }

    public function getBuyCount(): string
    {
        return $this->buyCount;
    }

    public function getQuote(): string
    {
        return $this->quote;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function setSellCount(string $sellCount): ProfitBotModel
    {
        $this->sellCount = $sellCount;

        return $this;
    }

    public function setBuyCount(string $buyCount): ProfitBotModel
    {
        $this->buyCount = $buyCount;

        return $this;
    }

    public function setTotalSold(string $totalSold): ProfitBotModel
    {
        $this->totalSold = $totalSold;

        return $this;
    }

    public function setTotalBought(string $totalBought): ProfitBotModel
    {
        $this->totalBought = $totalBought;

        return $this;
    }
}
