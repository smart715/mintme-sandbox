<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;

interface CryptoRatesFetcherInterface
{
    /** @throws FetchException */
    public function fetch(): array;
}
