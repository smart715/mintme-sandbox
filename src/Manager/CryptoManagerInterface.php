<?php

namespace App\Manager;

use App\Entity\Crypto;

interface CryptoManagerInterface
{
    public function findBySymbol(string $symbol): ?Crypto;

    public function findAll(): array;
}
