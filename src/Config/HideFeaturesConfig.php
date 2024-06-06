<?php declare(strict_types = 1);

namespace App\Config;

use App\Utils\Symbols;

class HideFeaturesConfig
{
    private bool $newMarketsEnabled;
    private bool $tokenConnectEnabled;
    private bool $rewardsEnabled;
    private array $enabledCryptos;

    public function __construct(
        bool $newMarketsEnabled,
        bool $tokenConnectEnabled,
        bool $rewardsEnabled,
        array $enabledCryptos
    ) {
        $this->newMarketsEnabled = $newMarketsEnabled;
        $this->tokenConnectEnabled = $tokenConnectEnabled;
        $this->rewardsEnabled = $rewardsEnabled;
        $this->enabledCryptos = $enabledCryptos;
    }

    /** @codeCoverageIgnore */
    public function isRewardsEnabled(): bool
    {
        return $this->rewardsEnabled;
    }

    /** @codeCoverageIgnore */
    public function isNewMarketsEnabled(): bool
    {
        return $this->newMarketsEnabled;
    }

    public function isCryptoEnabled(string $symbol): bool
    {
        if (Symbols::MINTME === $symbol) {
            $symbol = Symbols::WEB;
        }

        return $this->enabledCryptos[$symbol] ?? false;
    }

    /** @return array<string> */
    public function getAllEnabledCryptos(): array
    {
        $symbols = [];

        foreach ($this->enabledCryptos as $symbol => $status) {
            if ($status) {
                $symbols[] = $symbol;
            }
        }

        return $symbols;
    }

    /** @codeCoverageIgnore */
    public function isTokenConnectEnabled(): bool
    {
        return $this->tokenConnectEnabled;
    }
}
