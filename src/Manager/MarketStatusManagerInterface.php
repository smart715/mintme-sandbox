<?php declare(strict_types = 1);

namespace App\Manager;

interface MarketStatusManagerInterface
{
    public function getMarketsInfo(): array;
}
