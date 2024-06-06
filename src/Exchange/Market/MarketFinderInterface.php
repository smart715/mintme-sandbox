<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Exception\NotDeployedTokenException;
use App\Exchange\Market;

interface MarketFinderInterface
{
    /**
     * @throws NotDeployedTokenException thrown when a token is not deployed and $onlyDeployed is true
     */
    public function find(string $base, string $quote, bool $onlyDeployed = false): ?Market;
}
