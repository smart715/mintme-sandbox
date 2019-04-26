<?php declare(strict_types = 1);

namespace App\Wallet\Deposit;

use App\Entity\User;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Transaction;
use App\Wallet\RowsFetcherInterface;
use Money\Money;

interface DepositGatewayCommunicatorInterface extends RowsFetcherInterface
{
    public function getDepositCredentials(int $userId, array $predefinedToken): DepositCredentials;

    /** @return Transaction[] */
    public function getTransactions(User $user, int $offset, int $limit): array;

    public function getFee(string $crypto): Money;
}
