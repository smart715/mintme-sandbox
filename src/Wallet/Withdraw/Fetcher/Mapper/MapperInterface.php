<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Wallet\RowsFetcherInterface;
use Money\Money;

interface MapperInterface extends RowsFetcherInterface
{
    public function getBalance(TradableInterface $tradable, Crypto $cryptoNetwok): Money;

    public function isContractAddress(string $address, string $crypto): bool;

    public function getUserId(string $address, string $cryptoNetwork): ?int;

    public function getCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
