<?php declare(strict_types = 1);

namespace App\Communications;

interface CryptoSynchronizerInterface
{
    public function fetchCryptos(): array;
}
