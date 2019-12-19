<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CachedCryptoRatesFetcher;
use App\Communications\RestRpcInterface;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedCryptoRatesFetcherTest extends CryptoRatesFetcherTest
{
    public function testFetchCached(): void
    {
        $crf = new CachedCryptoRatesFetcher(
            $this->mockCryptoManager(),
            $this->mockRpc(),
            $this->mockcache(true)
        );

        $expectedResult = [
            'WEB' => [
                'BTC' => 10,
                'USD' => 20,
            ],
            'BTC' => [
                'BTC' => 1,
                'USD' => 2,
            ],
        ];

        $this->assertEquals($expectedResult, $crf->fetch());
    }

    public function testFetchNotCached(): void
    {
        $crf = new CachedCryptoRatesFetcher(
            $this->mockCryptoManager(),
            $this->mockRpc(),
            $this->mockcache(false)
        );

        $expectedResult = [
            'WEB' => [
                'BTC' => 10,
                'USD' => 20,
            ],
            'BTC' => [
                'BTC' => 1,
                'USD' => 2,
            ],
        ];

        $this->assertEquals($expectedResult, $crf->fetch());
    }

    protected function mockCache(bool $hit): CacheInterface
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(function ($key, $callback) use ($hit) {
            $result = [
                'WEB' => [
                    'BTC' => 10,
                    'USD' => 20,
                ],
                'BTC' => [
                    'BTC' => 1,
                    'USD' => 2,
                ],
            ];
            
            return $hit
            
                ? $result
            
                : $callback($this->createMock(ItemInterface::class));
        });

        return $cache;
    }
}
