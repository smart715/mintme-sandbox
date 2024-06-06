<?php declare(strict_types = 1);

namespace App\Manager\Model;

use App\Entity\InternalTransaction\InternalTransaction;

/** @codeCoverageIgnore */
class InternalTransferModel
{
    private InternalTransaction $internalWithdrawal;
    private InternalTransaction $internalDeposit;

    public function __construct(InternalTransaction $internalWithdrawal, InternalTransaction $internalDeposit)
    {
        $this->internalWithdrawal = $internalWithdrawal;
        $this->internalDeposit = $internalDeposit;
    }

    public function getInternalWithdrawal(): InternalTransaction
    {
        return $this->internalWithdrawal;
    }

    public function getInternalDeposit(): InternalTransaction
    {
        return $this->internalDeposit;
    }
}
