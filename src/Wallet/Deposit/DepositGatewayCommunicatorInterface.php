<?php declare(strict_types = 1);

namespace App\Wallet\Deposit;

use App\Entity\Crypto;
use App\Entity\User;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Transaction;
use App\Wallet\RowsFetcherInterface;

interface DepositGatewayCommunicatorInterface extends RowsFetcherInterface
{
    /**
     * @param int $userId
     * @param Crypto[] $cryptos
     * @return DepositCredentials
     */
    public function getDepositCredentials(int $userId, array $cryptos): DepositCredentials;

    /** @return Transaction[] */
    public function getTransactions(User $user, int $offset, int $limit): array;

    public function getDepositInfo(string $crypto): DepositInfo;
}
