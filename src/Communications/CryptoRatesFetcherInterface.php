<?php declare(strict_types = 1);

namespace App\Communications;

interface CryptoRatesFetcherInterface
{
    public function fetch(): array;
}
