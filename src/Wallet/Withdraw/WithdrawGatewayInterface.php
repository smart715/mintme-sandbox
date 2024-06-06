<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Model\Transaction;
use App\Wallet\RowsFetcherInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Money\Money;

interface WithdrawGatewayInterface extends RowsFetcherInterface
{
    public function withdraw(
        User $user,
        Money $balance,
        string $address,
        Crypto $crypto,
        ?Money $fee = null
    ): void;

    public function retryWithdraw(WithdrawCallbackMessage $callbackMessage): void;

    public function getBalance(TradableInterface $tradable, Crypto $cryptoNetwork): Money;

    /** @return Transaction[] */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array;

    public function isContractAddress(string $address, string $crypto): bool;

    public function getUserId(string $address, string $cryptoNetwork): ?int;

    public function getCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
