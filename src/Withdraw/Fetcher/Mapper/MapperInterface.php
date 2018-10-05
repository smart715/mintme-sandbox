<?php

namespace App\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Payment\Transaction;

interface MapperInterface
{
    /** @return Transaction[] */
    public function getHistory(User $user): array;

    /** @return mixed[] */
    public function getBalance(Crypto $crypto): array;
}
