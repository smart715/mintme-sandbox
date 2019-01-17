<?php

namespace App\Withdraw;

use App\Entity\Crypto;
use App\Entity\User;
use App\Fetcher\RowsFetcherInterface;
use App\Wallet\Model\Transaction;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Money\Money;

interface WithdrawGatewayInterface extends RowsFetcherInterface
{
    public function withdraw(User $user, Money $balance, string $address, Crypto $crypto): void;

    public function retryWithdraw(WithdrawCallbackMessage $callbackMessage): void;

    public function getBalance(Crypto $crypto): Money;

    /** @return Transaction[] */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array;
}
