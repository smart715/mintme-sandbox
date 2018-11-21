<?php

namespace App\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Payment\Transaction;

interface MapperInterface
{
    /** @return Transaction[] */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array;

    public function getBalance(Crypto $crypto): ?float;
}
