<?php declare(strict_types = 1);

namespace App\Communications;

use App\Manager\CryptoManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedCryptoRatesFetcher extends CryptoRatesFetcher
{
    private const CACHE_KEY = 'rates';

    /** @var CacheInterface */
    private $cache;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        RestRpcInterface $rpc,
        CacheInterface $cache
    ) {
        $this->cache = $cache;
        parent::__construct($cryptoManager, $rpc);
    }

    public function fetch(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return parent::fetch();
        });
    }
}
