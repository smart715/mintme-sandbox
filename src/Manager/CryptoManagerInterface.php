<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;

interface CryptoManagerInterface
{
    public function findBySymbol(string $symbol, bool $showHidden = false): ?Crypto;

    /** @return Crypto[] */
    public function findAll(bool $showHidden = false): array;

    public function findAllIndexed(string $index, bool $array = false, bool $showHidden = false): array;
}
