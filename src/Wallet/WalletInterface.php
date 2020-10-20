<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingWithdrawInterface;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Transaction;

interface WalletInterface
{
    /**
     * @param User $user
     * @param int $offset
     * @param int $limit
     * @return Transaction[]
     */
    public function getWithdrawDepositHistory(User $user, int $offset, int $limit): array;

    /**
     * @param User $user
     * @param Address $address
     * @param Amount $amount
     * @param TradebleInterface $tradable
     * @return PendingWithdrawInterface
     * @throws \Throwable
     * @throws NotEnoughAmountException
     * @throws NotEnoughUserAmountException
     */
    public function withdrawInit(
        User $user,
        Address $address,
        Amount $amount,
        TradebleInterface $tradable
    ): PendingWithdrawInterface;

    public function withdrawCommit(PendingWithdrawInterface $pendingWithdraw): void;

    /**
     * @param User $user
     * @param array $cryptos
     * @return array<Address>
     */
    public function getDepositCredentials(User $user, array $cryptos): array;

    /**
     * @param User $user
     * @return array<Address>
     */
    public function getTokenDepositCredentials(User $user): array;

    public function getDepositCredential(User $user, Crypto $crypto): Address;

    public function getDepositInfo(TradebleInterface $tradable): DepositInfo;
}
