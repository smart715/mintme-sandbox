<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Storage;

interface StorageAdapterInterface
{
    /** @return mixed[] */
    public function requestHistory(int $id, int $offset, int $limit, int $fromTimestamp): array;

    public function requestBalance(string $symbol, string $networkSymbol): string;

    public function requestAddressCode(string $address, string $crypto): string;

    public function requestUserId(string $address, string $cryptoNetwork): ?int;

    public function requestCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
