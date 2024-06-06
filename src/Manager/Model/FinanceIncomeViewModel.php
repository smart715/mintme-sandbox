<?php declare(strict_types = 1);

namespace App\Manager\Model;

/** @codeCoverageIgnore */
class FinanceIncomeViewModel
{
    /**
     * @var FinanceIncomeModel[]
     */
    private array $incomes = []; // phpcs:ignore

    private string $totalUsd = '0'; // phpcs:ignore

    /**
     * @return FinanceIncomeModel[]
     */
    public function getIncomes(): array
    {
        return $this->incomes;
    }

    /**
     * @param FinanceIncomeModel[] $incomes
     */
    public function setIncomes(array $incomes): self
    {
        $this->incomes = $incomes;

        return $this;
    }

    public function getTotalUsd(): string
    {
        return $this->totalUsd;
    }

    public function setTotalUsd(string $totalUsd): self
    {
        $this->totalUsd = $totalUsd;

        return $this;
    }
}
