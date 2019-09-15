<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DeployCostFetcher;
use App\Communications\Exception\FetchException;
use App\Communications\RestRpcInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currencies;
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

        $fetcher = new DeployCostFetcher($rpc, 49, $this->mockMoneyWrapper());
        $this->assertEquals(
            '24500000000000000000000',
            $fetcher->getDeployWebCost()->getAmount()
        );
    }

    public function testGetDeployWebCostWithUnexpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn('');

        $this->expectException(FetchException::class);

        $fetcher = new DeployCostFetcher($rpc, 49, $this->mockMoneyWrapper());
        $this->assertEquals(
            '24500000000000000000000',
            $fetcher->getDeployWebCost()>getAmount()
        );
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $currencies = $this->createMock(Currencies::class);
        $currencies->method('subunitFor')->willReturn(18);
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('getRepository')->willReturn($currencies);

        return $moneyWrapper;
    }
}
