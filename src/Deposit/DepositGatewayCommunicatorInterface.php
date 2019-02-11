<?php

namespace App\Deposit;

use App\Deposit\Model\DepositCredentials;
use App\Entity\User;
use App\Fetcher\RowsFetcherInterface;
use App\Wallet\Model\Transaction;

interface DepositGatewayCommunicatorInterface extends RowsFetcherInterface
{
    public function getDepositCredentials(int $userId, array $predefinedToken): DepositCredentials;

    /** @return Transaction[] */
    public function getTransactions(User $user, int $offset, int $limit): array;
}
