<?php declare(strict_types = 1);

namespace App\Communications\GeckoCoin;

use App\Communications\GeckoCoin\Model\SimplePrice;
use Symfony\Contracts\Cache\CacheInterface;

interface GeckoCoinCommunicatorInterface
{
    public function getSimplePrice(SimplePrice $simplePriceData): array;

    public function getCoinList(): array;

    public function fetchCryptoCirculatingSupply(string $symbol, CacheInterface $cache): string;
}
