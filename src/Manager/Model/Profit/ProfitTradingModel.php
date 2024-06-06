<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
class ProfitTradingModel extends AbstractProfitModel
{
    private string $base;
    private string $quote;
    private string $totalBaseFee;
    private string $totalBaseFeeInUsd;
    private string $totalQuoteFee;
    private string $totalQuoteFeeInUsd;

    public function __construct(
        string $symbol,
        string $base = '',
        string $quote = '',
        string $totalBaseFee = '0',
        string $totalBaseFeeInUsd = '0',
        string $totalQuoteFee = '0',
        string $totalQuoteFeeInUsd = '0',
        string $profitInUsd = '0',
        string $count = '0'
    ) {
        parent::__construct($symbol, $profitInUsd, $count);

        $this->base = $base;
        $this->quote = $quote;
        $this->totalBaseFee = $totalBaseFee;
        $this->totalBaseFeeInUsd = $totalBaseFeeInUsd;
        $this->totalQuoteFee = $totalQuoteFee;
        $this->totalQuoteFeeInUsd = $totalQuoteFeeInUsd;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function getQuote(): string
    {
        return $this->quote;
    }

    public function getTotalQuoteFee(): string
    {
        return $this->totalQuoteFee;
    }

    public function getTotalQuoteFeeInUsd(): string
    {
        return $this->totalQuoteFeeInUsd;
    }

    public function getTotalBaseFee(): string
    {
        return $this->totalBaseFee;
    }

    public function getTotalBaseFeeInUsd(): string
    {
        return $this->totalBaseFeeInUsd;
    }
}
