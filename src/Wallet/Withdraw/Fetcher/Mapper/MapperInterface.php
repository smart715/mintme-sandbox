<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Wallet\RowsFetcherInterface;
use Money\Money;

interface MapperInterface extends RowsFetcherInterface
{
    public function getBalance(Crypto $crypto): Money;

    public function isContractAddress(string $address, string $crypto): bool;
}
