<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CoinmarketcapCommunicator;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class CoinmarketcapCommunicatorTest extends TestCase
{
    public function testFetchCryptos(): void
    {
        $data = [
            [
                'name' => 'FooBar',
                'symbol' => 'foo',
            ],
            [
                'name' => 'BarBaz',
                'symbol' => 'bar',
            ],
            [
                'name' => 'BazQux',
                'symbol' => 'baz',
            ],
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        $communicator = new CoinmarketcapCommunicator($rpc);
        $this->assertEquals(
            [
                'names' => ['foobar', 'barbaz', 'bazqux'],
                'symbols' => ['foo', 'bar', 'baz'],
            ],
            $communicator->fetchCryptos()
        );
    }
}
