<?php declare(strict_types = 1);

namespace App\Communications;

interface CryptoSynchronizerInterface
{
    /** @return array<string> */
    public function fetchCryptos(): array;
}
