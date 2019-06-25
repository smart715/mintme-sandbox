<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
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
    public function withdrawInit(User $user, Address $address, Amount $amount, Crypto $crypto): PendingWithdraw;

    public function withdrawCommit(PendingWithdraw $pendingWithdraw): void;


    /** @return array<Address> */
    public function getDepositCredentials(User $user, array $cryptos): array;

    public function getDepositCredential(User $user, Crypto $crypto): Address;

    public function getFee(Crypto $crypto): Money;
}
