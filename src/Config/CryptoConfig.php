<?php declare(strict_types = 1);

namespace App\Config;

class CryptoConfig
{
    private array $defaultNetworks;

    public function __construct(array $defaultNetworks)
    {
        $this->defaultNetworks = $defaultNetworks;
    }

    public function getCryptoDefaultNetwork(string $symbol): ?string
    {
        return $this->defaultNetworks[$symbol] ?? null;
    }
}
