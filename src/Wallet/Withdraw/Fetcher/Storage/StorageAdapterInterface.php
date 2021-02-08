<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Storage;

interface StorageAdapterInterface
{
    /** @return mixed[] */
    public function requestHistory(int $id, int $offset, int $limit): array;

    public function requestBalance(string $symbol): string;

    public function requestAddressCode(string $address): bool;
}
