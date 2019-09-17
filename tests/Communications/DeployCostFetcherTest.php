<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DeployCostFetcher;
use App\Communications\Exception\FetchException;
use App\Communications\RestRpcInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
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

        (new DeployCostFetcher(
            $rpc,
            49,
            $this->mockMoneyWrapper($this->once())
        ))->getDeployWebCost();
    }

    public function testGetDeployWebCostWithUnexpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn('');

        $this->expectException(FetchException::class);

        (new DeployCostFetcher(
            $rpc,
            49,
            $this->mockMoneyWrapper($this->never())
        ))->getDeployWebCost();
    }

    private function mockMoneyWrapper(Invocation $invocation): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->expects($invocation)->method('convert');

        return $moneyWrapper;
    }
}
