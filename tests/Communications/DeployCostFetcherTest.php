<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DeployCostFetcher;
use App\Communications\Exception\FetchException;
use App\Communications\RestRpcInterface;
use App\Exchange\Config\DeployCostConfig;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
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
            'ethereum' => [
                'usd' => .002,
            ],
            'binancecoin' => [
                'usd' => .002,
            ],
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        (new DeployCostFetcher(
            $rpc,
            new DeployCostConfig(49, 1, 1, 1, 1, .01, .01, 0.1),
            $this->mockMoneyWrapper($this->once())
        ))->getDeployCost('WEB');
    }

    public function testGetDeployWebCostWithUnexpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn('');

        $this->expectException(FetchException::class);

        (new DeployCostFetcher(
            $rpc,
            new DeployCostConfig(49, 1, 1, 1, 1, .01, .01, 0.1),
            $this->mockMoneyWrapper($this->never())
        ))->getDeployCost('WEB');
    }

    public function testGetDeployCostReferralReward(): void
    {
        $data = [
            'webchain' => [
                'usd' => .002,
            ],
            'ethereum' => [
                'usd' => .002,
            ],
            'binancecoin' => [
                'usd' => .002,
            ],
        ];

        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturn(json_encode($data));

        $deployCostReferralReward = (new DeployCostFetcher(
            $rpc,
            new DeployCostConfig(100, 1, 1, 1, 1, 0.15, .01, .01),
            $this->mockMoneyWrapper($this->once())
        ))->getDeployCostReferralReward('WEB');

        $this->assertEquals('150000000000000000', $deployCostReferralReward->getAmount());
        $this->assertEquals(Symbols::WEB, $deployCostReferralReward->getCurrency());
    }

    private function mockMoneyWrapper(Invocation $invocation): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->expects($invocation)->method('convert')
            ->willReturn(new Money('1000000000000000000', new Currency(Symbols::WEB)));

        return $moneyWrapper;
    }
}
