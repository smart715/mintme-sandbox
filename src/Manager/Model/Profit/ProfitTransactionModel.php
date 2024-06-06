<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
class ProfitTransactionModel extends AbstractProfitModel
{
    private string $totalDeposit;
    private string $totalWithdraw;
    private string $totalDepositFee;
    private string $totalWithdrawFee;
    private string $profit;

    public function __construct(
        string $symbol,
        string $totalDeposit = '0',
        string $totalWithdraw = '0',
        string $totalDepositFee = '0',
        string $totalWithdrawFee = '0',
        string $profit = '0',
        string $profitInUsd = '0'
    ) {
        parent::__construct($symbol, $profitInUsd, '0');

        $this->totalDeposit = $totalDeposit;
        $this->totalWithdraw = $totalWithdraw;
        $this->totalDepositFee = $totalDepositFee;
        $this->totalWithdrawFee = $totalWithdrawFee;
        $this->profit = $profit;
    }

    public function getTotalDeposit(): string
    {
        return $this->totalDeposit;
    }

    public function getTotalWithdraw(): string
    {
        return $this->totalWithdraw;
    }

    public function getTotalDepositFee(): string
    {
        return $this->totalDepositFee;
    }

    public function getTotalWithdrawFee(): string
    {
        return $this->totalWithdrawFee;
    }

    public function getProfit(): string
    {
        return $this->profit;
    }
}
