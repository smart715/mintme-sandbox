<?php declare(strict_types = 1);

namespace App\Manager\Finance;

use App\Entity\Crypto;
use App\Manager\Model\FinanceBalanceModel;
use App\Manager\Model\FinanceIncomeViewModel;

interface FinanceBalanceManagerInterface
{
    /**
     * @param Crypto|null $crypto
     * @return FinanceBalanceModel[]
     */
    public function getBalance(?Crypto $crypto = null): array;
    
    public function getIncome(\DateTimeImmutable $from, \DateTimeImmutable $to): FinanceIncomeViewModel;
}
