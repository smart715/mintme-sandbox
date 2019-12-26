<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CryptoRatesFetcher;
use App\Communications\RestRpcInterface;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CryptoRatesFetcherTest extends TestCase
{
    public function testFetch(): void
    {
        $crf = new CryptoRatesFetcher(
            $this->mockCryptoManager(),
            $this->mockRpc()
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

    protected function mockCryptoManager(): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->method('findAllIndexed')->with('name')->willReturn([
            'Webchain' => $this->mockCrypto('Webchain', 'WEB'),
            'Bitcoin' => $this->mockCrypto('Bitcoin', 'BTC'),
        ]);

        return $cm;
    }

    protected function mockCrypto(string $name, string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getName')->willReturn($name);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    protected function mockRpc(): RestRpcInterface
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->with(
            "simple/price?ids=Webchain,Bitcoin&vs_currencies=WEB,BTC,USD",
            Request::METHOD_GET
        )->willReturn(json_encode([
            'webchain' => [
                'btc' => 10,
                'usd' => 20,
            ],
            'bitcoin' => [
                'btc' => 1,
                'usd' => 2,
            ],
        ]));

        return $rpc;
    }
}