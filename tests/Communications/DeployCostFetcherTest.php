<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DeployCostFetcher;
use App\Communications\Exception\FetchException;
use App\Communications\ExternalServiceIdsMapperInterface;
use App\Communications\GeckoCoin\Config\GeckoCoinConfig;
use App\Communications\RestRpcInterface;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Exchange\Config\DeployCostConfig;
use App\Manager\CryptoManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\CryptoCalculator;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DeployCostFetcherTest extends TestCase
{
    private array $data;

    protected function setUp(): void
    {
        $this->data = [
            'webchain' => [
                'usd' => .002,
            ],
            'ethereum' => [
                'usd' => .002,
            ],
            'binancecoin' => [
                'usd' => .002,
            ],
            'crypto-com-chain' => [
                'usd' => .002,
            ],
        ];
    }

    public function testGetCostWithExpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->expects($this->once())->method('send')->willReturn(json_encode($this->data));
        $config = new DeployCostConfig(
            $this->getSymbolArray(49, 1, 1, 1),
            $this->getSymbolArray(0, 1, 1, 1),
            $this->getSymbolArray(.01, .01, 0.1, 1),
        );

        (new DeployCostFetcher(
            $config,
            $this->mockCryptoCalculator(),
            $rpc,
            $this->mockMoneyWrapper($this->once()),
            $this->mockCryptoManager(),
            $this->mockCache($this->data),
            $this->mockDisableServicesConfig(),
            $this->mockIdsMapper(),
            $this->mockHideFeaturesConfig(),
            $this->mockLogger(),
            $this->mockRebrandingConverter()
        ))->getCost('WEB');
    }

    public function testGetCostWithUnexpectedResponse(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->expects($this->once())->method('send')->willReturn('');

        $this->expectException(FetchException::class);

        $config = new DeployCostConfig(
            $this->getSymbolArray(49, 1, 1, 1),
            $this->getSymbolArray(0, 1, 1, 1),
            $this->getSymbolArray(.01, .01, 0.1, 1),
        );

        (new DeployCostFetcher(
            $config,
            $this->mockCryptoCalculator(),
            $rpc,
            $this->mockMoneyWrapper($this->never()),
            $this->mockCryptoManager(),
            $this->mockCache(),
            $this->mockDisableServicesConfig(),
            $this->mockIdsMapper(),
            $this->mockHideFeaturesConfig(),
            $this->mockLogger(),
            $this->mockRebrandingConverter()
        ))->getCost('WEB');
    }

    public function testGetDeployCostReferralReward(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->expects($this->once())->method('send')->willReturn(json_encode($this->data));

        $config = new DeployCostConfig(
            $this->getSymbolArray(100, 1, 1, 1),
            $this->getSymbolArray(0, 1, 1, 1),
            $this->getSymbolArray(.15, .01, .01, 1),
        );

        $deployCostReferralReward = (new DeployCostFetcher(
            $config,
            $this->mockCryptoCalculator(),
            $rpc,
            $this->mockMoneyWrapper($this->once()),
            $this->mockCryptoManager(),
            $this->mockCache($this->data),
            $this->mockDisableServicesConfig(),
            $this->mockIdsMapper(),
            $this->mockHideFeaturesConfig(),
            $this->mockLogger(),
            $this->mockRebrandingConverter()
        ))->getDeployCostReferralReward('WEB');

        $this->assertEquals('300000000000000000', $deployCostReferralReward->getAmount());
        $this->assertEquals(Symbols::WEB, $deployCostReferralReward->getCurrency());
    }

    public function testGetDeployCostReferralRewardNotWEB(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->expects($this->once())->method('send')->willReturn(json_encode($this->data));

        $config = new DeployCostConfig(
            $this->getSymbolArray(100, 1, 1, 1),
            $this->getSymbolArray(0, 1, 1, 1),
            $this->getSymbolArray(.15, .01, .01, 1),
        );

        $expectedWebAmount = '1000000000000000000';
        $deployConstFetcher = new DeployCostFetcher(
            $config,
            $this->mockCryptoCalculator($expectedWebAmount),
            $rpc,
            $this->mockMoneyWrapper($this->once()),
            $this->mockCryptoManager(),
            $this->mockCache($this->data),
            $this->mockDisableServicesConfig(),
            $this->mockIdsMapper(),
            $this->mockHideFeaturesConfig(),
            $this->mockLogger(),
            $this->mockRebrandingConverter()
        );

        $deployCostReferralReward = $deployConstFetcher->getDeployCostReferralReward('ETH');

        $this->assertEquals($expectedWebAmount, $deployCostReferralReward->getAmount());
        $this->assertEquals(Symbols::WEB, $deployCostReferralReward->getCurrency());
    }

    public function testGetCosts(): void
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->expects($this->once())->method('send')->willReturn(json_encode($this->data));
        $config = new DeployCostConfig(
            $this->getSymbolArray(49, 1, 1, 1),
            $this->getSymbolArray(0, 1, 1, 1),
            $this->getSymbolArray(.01, .01, 0.1, 1),
        );

        (new DeployCostFetcher(
            $config,
            $this->mockCryptoCalculator(),
            $rpc,
            $this->mockMoneyWrapper($this->exactly(4)),
            $this->mockCryptoManager(),
            $this->mockCache($this->data),
            $this->mockDisableServicesConfig(),
            $this->mockIdsMapper(),
            $this->mockHideFeaturesConfig(),
            $this->mockLogger(),
            $this->mockRebrandingConverter()
        ))->getCosts();
    }

    private function getSymbolArray(float $web, float $eth, float $bnb, float $cro): array
    {
        return [
            'MINTME' => $web,
            'ETH' => $eth,
            'BNB' => $bnb,
            'CRO' => $cro,
        ];
    }

    private function mockMoneyWrapper(InvokedCount $invocation): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->expects($invocation)->method('convert')
            ->willReturn(new Money('1000000000000000000', new Currency(Symbols::WEB)));
        $moneyWrapper->method('parse')
            ->willReturn(new Money('1000000000000000000', new Currency(Symbols::WEB)));
        $moneyWrapper->method('format')
            ->willReturn('1000000000000000000');

        return $moneyWrapper;
    }

    private function mockCryptoManager(string $symbol = 'WEB'): CryptoManagerInterface
    {
        $crypto = $this->mockCrypto($symbol);

        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method('findBySymbol')
            ->willReturn($crypto);

        $cryptoManager
            ->method('findAll')
            ->willReturn([
                $this->mockCrypto(Symbols::WEB),
                $this->mockCrypto(Symbols::ETH),
                $this->mockCrypto(Symbols::BNB),
                $this->mockCrypto(Symbols::CRO),
            ]);

        return $cryptoManager;
    }

    private function mockCache(?array $prices = null): CacheInterface
    {
        $cache = $this->createMock(CacheInterface::class);
        $item = $this->createMock(ItemInterface::class);

        # mock cache return value but still test the callback function
        $cache->method('get')->willReturnCallback(function ($key, $fn) use ($item, $prices) {
            $fn($item);

            return $prices;
        });

        return $cache;
    }

    private function mockDisableServicesConfig(): DisabledServicesConfig
    {
        $mock = $this->createMock(DisabledServicesConfig::class);

        $mock->method('getBlockchainDeployStatus')->willReturn([
            Symbols::WEB => true,
            Symbols::BNB => true,
            Symbols::ETH => true,
            Symbols::CRO => true,
        ]);

        return $mock;
    }

    private function mockIdsMapper(): ExternalServiceIdsMapperInterface
    {
        return new GeckoCoinConfig([], [
            Symbols::WEB => 'webchain',
            Symbols::BNB => 'binancecoin',
            Symbols::ETH => 'ethereum',
            Symbols::CRO => 'crypto-com-chain',
        ]);
    }

    private function mockHideFeaturesConfig(): HideFeaturesConfig
    {
        $mock = $this->createMock(HideFeaturesConfig::class);

        $mock->method('isCryptoEnabled')->willReturn(true);

        return $mock;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockCryptoCalculator(
        ?string $returnWebAmount = null
    ): CryptoCalculator {
        $cryptoCalculator = $this->createMock(CryptoCalculator::class);
        $cryptoCalculator
            ->method('getMintmeWorth')
            ->willReturn(new Money($returnWebAmount, new Currency(Symbols::WEB)));

        return $cryptoCalculator;
    }

    private function mockRebrandingConverter(): RebrandingConverterInterface
    {
        $mock = $this->createMock(RebrandingConverterInterface::class);

        $mock->method('convert')->willReturnArgument(0);

        return $mock;
    }

    public function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto
            ->method('getSymbol')
            ->willReturn($symbol);
        $crypto
            ->method('getMoneySymbol')
            ->willReturn($symbol);

        $crypto->method('getShowSubunit')
            ->willReturn(8);

        $crypto->method('isAsset')
            ->willReturn(true);

        return $crypto;
    }
}
