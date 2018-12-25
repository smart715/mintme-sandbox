<?php

namespace App\Withdraw;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use App\Withdraw\Payment\Transaction;
use Money\Money;

interface WithdrawGatewayInterface
{
    public function withdraw(User $user, Money $balance, string $address, Crypto $crypto): void;

    public function retryWithdraw(WithdrawCallbackMessage $callbackMessage): void;

    /** @return Transaction[] */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array;

    public function getBalance(Crypto $crypto): Money;
}
