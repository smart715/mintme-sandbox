<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Exchange\Market\Model\Summary;

interface MarketSummaryFactoryInterface
{
    /**
     * @return Summary[]
     */
    public function create(): array;
}
