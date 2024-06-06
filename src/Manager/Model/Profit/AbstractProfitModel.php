<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
abstract class AbstractProfitModel
{
    private string $symbol;
    private string $profitInUsd;
    private string $count;

    public function __construct(string $symbol, string $profitInUsd, string $count)
    {
        $this->symbol = $symbol;
        $this->profitInUsd = $profitInUsd;
        $this->count = $count;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getProfitInUsd(): string
    {
        return $this->profitInUsd;
    }

    public function getCount(): string
    {
        return $this->count;
    }

    public function setSymbol(string $symbol): AbstractProfitModel
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function setProfitInUsd(string $profitInUsd): AbstractProfitModel
    {
        $this->profitInUsd = $profitInUsd;

        return $this;
    }

    public function setCount(string $count): AbstractProfitModel
    {
        $this->count = $count;

        return $this;
    }
}
