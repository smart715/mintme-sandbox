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
use App\Wallet\Model\Transaction;
use Money\Money;

interface WalletInterface
{
    /** @return Transaction[] */
    public function getWithdrawDepositHistory(User $user, int $offset, int $limit): array;

    /**
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


    /** @return array<Address> */
    public function getDepositCredentials(User $user, array $cryptos): array;

    /** @return array<Address> */
    public function getTokenDepositCredentials(User $user): array;

    public function getDepositCredential(User $user, Crypto $crypto): Address;

    public function getFee(TradebleInterface $tradable): Money;
}
