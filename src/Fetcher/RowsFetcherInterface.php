<?php

namespace App\Fetcher;

use App\Entity\User;
use App\Wallet\Model\Transaction;

interface RowsFetcherInterface
{
    /** @return Transaction[] */
    public function getHistory(User $user, int $offset, int $limit): array;
}
