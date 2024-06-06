<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\Exception\FetchException;
use App\Communications\ExternalServiceIdsMapperInterface;
use App\Communications\GeckoCoin\Config\GeckoCoinConfig;
use App\Communications\MarketCostFetcher;
use App\Communications\RestRpcInterface;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Exchange\Config\TokenMarketConfig;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MarketCostFetcherTest extends TestCase
{
    public function testGetCost(): void
    {
        $responseData = [
            'webchain' => ['usd' => 0.01],
            'bitcoin' => ['usd' => 0.01],
            'ethereum' => ['usd' => 0.01],
            'binancecoin' => ['usd' => 0.01],
            'usd-coin' => ['usd' => 0.01],
            'crypto-com-chain' => ['usd' => 0.01],
        ];

        $marketCostFetcher = new MarketCostFetcher(
            $this->mockTokenMarketConfig(),
            $this->mockMoneyWrapper(count($responseData)),
            $this->mockRestRpc($responseData),
            $this->mockHideFeaturesConfig(),
            $this->mockCryptoManager(),
            $this->mockIdsMapper(),
            $this->mockLogger()
        );

        $this->assertEquals(
            [
                'WEB' => $this->dummyMoneyObject(),
                'BTC' => $this->dummyMoneyObject(),
                'ETH' => $this->dummyMoneyObject(),
                'BNB' => $this->dummyMoneyObject(),
                'USDC' => $this->dummyMoneyObject(),
                'CRO' => $this->dummyMoneyObject(),
            ],
            $marketCostFetcher->getCost('WEB')
        );
    }

    public function testGetCostWithInvalidResponseWillRaiseException(): void
    {
        $responseData = [
            'webchain' => ['INVALID_KEY' => 0.01],
            'bitcoin' => ['usd' => 0.01],
            'ethereum' => ['usd' => 0.01],
            'binancecoin' => ['usd' => 0.01],
            'usd-coin' => ['usd' => 0.01],
            'crypto-com-chain' => ['usd' => 0.01],
        ];

        $marketCostFetcher = new MarketCostFetcher(
            $this->mockTokenMarketConfig(),
            $this->mockMoneyWrapper(0),
            $this->mockRestRpc($responseData),
            $this->mockHideFeaturesConfig(),
            $this->mockCryptoManager(),
            $this->mockIdsMapper(),
            $this->mockLogger()
        );

        $this->expectException(FetchException::class);

        $marketCostFetcher->getCost('WEB');
    }

    public function testGetCosts(): void
    {
        $responseData = [
            'webchain' => ['usd' => 0.01],
            'bitcoin' => ['usd' => 0.01],
            'ethereum' => ['usd' => 0.01],
            'binancecoin' => ['usd' => 0.01],
            'usd-coin' => ['usd' => 0.01],
            'crypto-com-chain' => ['usd' => 0.01],
        ];

        $configData = [
            "BTC" => 100,
            "ETH" => 100,
            "BNB" => 100,
            "CRO" => 100,
            "USDC" => 100,
        ];

        $marketCostPerCurrency = [
            'WEB' => $this->dummyMoneyObject(),
            'BTC' => $this->dummyMoneyObject(),
            'ETH' => $this->dummyMoneyObject(),
            'BNB' => $this->dummyMoneyObject(),
            'USDC' => $this->dummyMoneyObject(),
            'CRO' => $this->dummyMoneyObject(),
        ];

        $marketCostFetcher = new MarketCostFetcher(
            $this->mockTokenMarketConfig($configData),
            $this->mockMoneyWrapper(count($responseData) * count($configData)),
            $this->mockRestRpc($responseData),
            $this->mockHideFeaturesConfig(),
            $this->mockCryptoManager(),
            $this->mockIdsMapper(),
            $this->mockLogger()
        );

        $this->assertEquals(
            [
                'BTC' => $marketCostPerCurrency,
                'ETH' => $marketCostPerCurrency,
                'BNB' => $marketCostPerCurrency,
                'CRO' => $marketCostPerCurrency,
                'USDC' => $marketCostPerCurrency,
            ],
            $marketCostFetcher->getCosts()
        );
    }

    public function testGetCostsWithInvalidResponseWillRaiseException(): void
    {
        $responseData = [
            'webchain' => ['INVALID_KEY' => 0.01],
            'bitcoin' => ['usd' => 0.01],
            'ethereum' => ['usd' => 0.01],
            'binancecoin' => ['usd' => 0.01],
            'usd-coin' => ['usd' => 0.01],
            'crypto-com-chain' => ['usd' => 0.01],
        ];

        $configData = [
            'bitcoin' => ['usd' => 0.01],
            'ethereum' => ['usd' => 0.01],
            'binancecoin' => ['usd' => 0.01],
            'usd-coin' => ['usd' => 0.01],
            'crypto-com-chain' => ['usd' => 0.01],
        ];

        $marketCostFetcher = new MarketCostFetcher(
            $this->mockTokenMarketConfig($configData),
            $this->mockMoneyWrapper(0),
            $this->mockRestRpc($responseData),
            $this->mockHideFeaturesConfig(),
            $this->mockCryptoManager(),
            $this->mockIdsMapper(),
            $this->mockLogger()
        );

        $this->expectException(FetchException::class);

        $marketCostFetcher->getCosts();
    }

    private function mockTokenMarketConfig(array $data = []): TokenMarketConfig
    {
        $tokenMarketConfig = $this->createMock(TokenMarketConfig::class);
        $tokenMarketConfig->method('getAllMarketCosts')->willReturn($data);

        return $tokenMarketConfig;
    }

    private function mockMoneyWrapper(int $invokeCount = 0): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->expects($invokeCount ? $this->exactly($invokeCount) : $this->never())
            ->method('parse')
            ->willReturn($this->dummyMoneyObject());

        $moneyWrapper->expects($invokeCount ? $this->exactly($invokeCount) : $this->never())
            ->method('convert')
            ->willReturn($this->dummyMoneyObject());

        return $moneyWrapper;
    }

    private function mockRestRpc(array $responseData = []): RestRpcInterface
    {
        $restRpc = $this->createMock(RestRpcInterface::class);
        $restRpc->expects($this->once())
            ->method('send')
            ->willReturn(json_encode($responseData));

        return $restRpc;
    }

    private function dummyMoneyObject(string $amount = '1', string $symbol = 'TOK'): Money
    {
        return new Money($amount, new Currency($symbol));
    }

    private function mockHideFeaturesConfig(): HideFeaturesConfig
    {
        $config = $this->createMock(HideFeaturesConfig::class);
        $config
            ->method('isCryptoEnabled')
            ->willReturn(true);

        return $config;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManagerMock = $this->createMock(CryptoManagerInterface::class);
        $cryptoManagerMock
            ->method('findAllAssets')
            ->willReturn([
                $this->mockCrypto('WEB'),
                $this->mockCrypto('BTC'),
                $this->mockCrypto('ETH'),
                $this->mockCrypto('BNB'),
                $this->mockCrypto('USDC'),
                $this->mockCrypto('CRO'),
            ])
        ;

        return $cryptoManagerMock;
    }

    private function mockIdsMapper(): ExternalServiceIdsMapperInterface
    {
        return new GeckoCoinConfig([], [
            Symbols::WEB => 'webchain',
            Symbols::BNB => 'binancecoin',
            Symbols::ETH => 'ethereum',
            Symbols::CRO => 'crypto-com-chain',
            Symbols::USDC => 'usd-coin',
            Symbols::BTC => 'bitcoin',
        ]);
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);

        $cryptoMock->method('getSymbol')->willReturn($symbol);
        $cryptoMock->method('getMoneySymbol')->willReturn($symbol);

        return $cryptoMock;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
