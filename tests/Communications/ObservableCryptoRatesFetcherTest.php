<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\ObservableCryptoRatesFetcher;
use App\Events\CryptoRatesRefreshedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ObservableCryptoRatesFetcherTest extends CryptoRatesFetcherTest
{
    public function testFetch(): void
    {
        $crf = new ObservableCryptoRatesFetcher(
            $this->mockCryptoRatesFetcher(),
            $this->mockEventDispatcher()
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

    protected function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $crf = $this->createMock(CryptoRatesFetcherInterface::class);
        $crf->expects($this->once())->method('fetch')->willReturn([
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

    protected function mockEventDispatcher(): EventDispatcherInterface
    {
        $ed = $this->createMock(EventDispatcherInterface::class);
        $ed->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(CryptoRatesRefreshedEvent::class),
            CryptoRatesRefreshedEvent::NAME
        );

        return $ed;
    }
}
