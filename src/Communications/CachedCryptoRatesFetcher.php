<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedCryptoRatesFetcher implements CryptoRatesFetcherInterface
{
    private const CACHE_KEY = 'rates';

    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private CacheInterface $cache;

    public function __construct(
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        CacheInterface $cache
    ) {
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->cache = $cache;
    }

    public function fetch(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return $this->cryptoRatesFetcher->fetch();
        });
    }
}
