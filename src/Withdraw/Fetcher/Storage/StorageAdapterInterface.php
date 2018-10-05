<?php

namespace App\Withdraw\Fetcher\Storage;

interface StorageAdapterInterface
{
    /** @return mixed[] */
    public function requestHistory(int $id): array;
    /** @return mixed[] */
    public function requestBalance(string $symbol): array;
}
