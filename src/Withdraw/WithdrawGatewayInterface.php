<?php

namespace App\Withdraw;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Payment\Transaction;

interface WithdrawGatewayInterface
{
    public function withdraw(User $user, string $balance, string $address, Crypto $crypto): void;
    /** @return Transaction[] */
    public function getHistory(User $user): array;
    /** @return mixed[] */
    public function getBalance(Crypto $crypto): array;
}
