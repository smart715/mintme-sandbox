<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Storage;

use App\Entity\Crypto;

interface StorageAdapterInterface
{
    /** @return mixed[] */
    public function requestHistory(int $id, int $offset, int $limit): array;

    public function requestBalance(string $symbol): string;

    public function requestAddress(string $address, Crypto $crypto): bool;
}
