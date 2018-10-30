<?php

namespace App\Manager;

use App\Entity\Crypto;

interface CryptoManagerInterface
{
    public function findBySymbol(string $symbol): ?Crypto;

    public function findBySymbols(array $symbols): array;

    public function findAll(): array;
}
