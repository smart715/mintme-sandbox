<?php declare(strict_types = 1);

namespace App\Communications\GeckoCoin\Config;

use App\Communications\ExternalServiceIdsMapperInterface;
use App\Exception\GeckoCoinConfigException;

class GeckoCoinConfig implements ExternalServiceIdsMapperInterface
{
    private array $geckoCoinMethodConfig;
    private array $cryptosConfig;
    private array $cryptoConfigFlipped;

    public function __construct(array $geckoCoinMethodConfig, array $cryptosConfig)
    {
        $this->geckoCoinMethodConfig = $geckoCoinMethodConfig;
        $this->cryptosConfig = $cryptosConfig;
        $this->cryptoConfigFlipped = array_flip($cryptosConfig);
    }

    public function getSimplePriceMethod(): string
    {
        if (!isset($this->geckoCoinMethodConfig['simple_price'])) {
            throw new GeckoCoinConfigException('simple_price key does not exist');
        }

        return $this->geckoCoinMethodConfig['simple_price'];
    }

    public function getCoinListMethod(): string
    {
        if (!isset($this->geckoCoinMethodConfig['coin_list'])) {
            throw new GeckoCoinConfigException('coin_list key does not exist');
        }

        return $this->geckoCoinMethodConfig['coin_list'];
    }

    public function getCryptos(): array
    {
        return $this->cryptosConfig;
    }

    public function getCryptoId(string $symbol): ?string
    {
        return $this->cryptosConfig[$symbol] ?? null;
    }

    public function getSymbolFromId(string $id): ?string
    {
        return $this->cryptoConfigFlipped[$id] ?? null;
    }
}
