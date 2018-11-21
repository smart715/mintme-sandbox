<?php

namespace App\Withdraw\Fetcher\Storage;

interface StorageAdapterInterface
{
    /** @return mixed[] */
    public function requestHistory(int $id, int $offset, int $limit): array;

    public function requestBalance(string $symbol): ?float;
}
