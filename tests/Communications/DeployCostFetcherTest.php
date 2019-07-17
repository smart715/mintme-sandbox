<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DeployCostFetcher;
use App\Communications\Exception\FetchException;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class DeployCostFetcherTest extends TestCase
{
    public function testGetDeployWebCostWithExpectedResponse(): void
    {
        $data = [
            'webchain' => [
                'usd' => .002,
            ],
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        $fetcher = new DeployCostFetcher($rpc, 49);
        $this->assertEquals(
            '24500.00000000',
            $fetcher->getDeployWebCost()
        );
    }

    public function testGetDeployWebCostWithUnexpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn('');

        $this->expectException(FetchException::class);

        $fetcher = new DeployCostFetcher($rpc, 49);
        $this->assertEquals(
            '24500.00000000',
            $fetcher->getDeployWebCost()
        );
    }
}
