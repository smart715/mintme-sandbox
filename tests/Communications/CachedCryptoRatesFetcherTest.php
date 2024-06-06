<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CachedCryptoRatesFetcher;
use App\Communications\CryptoRatesFetcherInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedCryptoRatesFetcherTest extends CryptoRatesFetcherTest
{
    public function testFetchCached(): void
    {
        $crf = new CachedCryptoRatesFetcher(
            $this->mockCryptoRatesFetcher(false),
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
            $this->mockCryptoRatesFetcher(true),
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

    protected function mockCryptoRatesFetcher(bool $fetchShouldBeCalled): CryptoRatesFetcherInterface
    {
        $crf = $this->createMock(CryptoRatesFetcherInterface::class);
        $crf->expects($fetchShouldBeCalled ? $this->once() : $this->never())
            ->method('fetch')
            ->willReturn([
                'WEB' => [
                    'BTC' => 10,
                    'USD' => 20,
                ],
                'BTC' => [
                    'BTC' => 1,
                    'USD' => 2,
                ],
            ]);

        return $crf;
    }
}
