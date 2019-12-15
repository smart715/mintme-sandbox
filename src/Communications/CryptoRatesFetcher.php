<?php declare(strict_types = 1);

namespace App\Communications;

use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CryptoRatesFetcher
{
    private const CACHE_KEY = 'rates';

    /** @var CacheInterface */
    private $cache;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(
        CacheInterface $cache,
        CryptoManagerInterface $cryptoManager,
        RestRpcInterface $rpc
    ) {
        $this->cache = $cache;
        $this->cryptoManager = $cryptoManager;
        $this->rpc = $rpc;
    }

    private function fetch(): array
    {
        $cryptos = $this->cryptoManager->findAllIndexed('name');

        $names = implode(',', array_map(function ($crypto) {
            return $crypto->getName();
        }, $cryptos));

        $symbols = implode(',', array_map(function ($crypto) {
            return $crypto->getSymbol();
        }, $cryptos));

        $symbols .= ','.strtolower(MoneyWrapper::USD_SYMBOL);

        $response = $this->rpc->send("simple/price?ids={$names}&vs_currencies={$symbols}", Request::METHOD_GET);

        $response = json_decode($response, true);

        array_walk($response, function (&$value, &$index) use ($cryptos): void {
            $index = $cryptos[ucfirst($index)]->getSymbol();

            array_walk($value, function (&$value, &$index): void {
                $index = strtoupper($index);
            });
        });

        return $response;
    }

    public function get(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return $this->fetch();
        });
    }
}
