<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\WithdrawInfo;

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
     * @param TradableInterface $tradable
     * @return PendingWithdrawInterface
     * @throws \Throwable
     * @throws NotEnoughAmountException
     * @throws NotEnoughUserAmountException
     */
    public function withdrawInit(
        User $user,
        Address $address,
        Amount $amount,
        TradableInterface $tradable,
        Crypto $cryptoNetwork
    ): PendingWithdrawInterface;

    public function withdrawCommit(PendingWithdrawInterface $pendingWithdraw): void;

    /**
     * @param User $user
     * @param Crypto[] $cryptos
     * @return Address[]
     */
    public function getDepositCredentials(User $user, array $cryptos): array;

    /**
     * @param User $user
     * @return array<Address>
     */
    public function getTokenDepositCredentials(User $user): array;

    public function getDepositCredential(User $user, Crypto $crypto): Address;

    public function getDepositInfo(
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        ?User $user = null
    ): ?DepositInfo;

    public function getWithdrawInfo(Crypto $cryptoNetwork, TradableInterface $tradable): ?WithdrawInfo;
}
