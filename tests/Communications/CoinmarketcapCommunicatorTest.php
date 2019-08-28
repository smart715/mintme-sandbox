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
                'id' => '1',
                'symbol' => 'FOO',
                'name' => 'FooBar',
            ],
            [
                'id' => '2',
                'symbol' => 'BAR',
                'name' => 'BarBaz',
            ],
            [
                'id' => '3',
                'symbol' => 'BAZ',
                'name' => 'BazQux',
            ],
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        $communicator = new CoinmarketcapCommunicator($rpc);
        $this->assertEquals(
            ['1', 'foo', 'foobar', '2', 'bar', 'barbaz', '3', 'baz', 'bazqux'],
            $communicator->fetchCryptos()
        );
    }
}
