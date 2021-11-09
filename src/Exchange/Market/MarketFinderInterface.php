<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Exchange\Market;

interface MarketFinderInterface
{
    public function find(string $base, string $quote): ?Market;
}
