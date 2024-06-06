<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Communications\CryptoRatesFetcherInterface;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Config\MarketPairsConfig;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManager;
use App\Repository\MarketStatusRepository;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MarketStatusManagerTest extends TestCase
{
    public function testGetTokenHighestPrice(): void
    {
        $marketStatusRepository = $this->mockMarketStatusRepository();
        $cryptoRatesFetcher = $this->mockCryptoRatesFetcher();

        $markets = [
            Symbols::WEB => $this->mockMarket(Symbols::WEB, 'test1', true),
            Symbols::BTC => $this->mockMarket(Symbols::BTC, 'test2', true),
            Symbols::BNB => $this->mockMarket(Symbols::BNB, 'test3', true),
            Symbols::ETH => $this->mockMarket(Symbols::ETH, 'test4', true),
        ];

        $marketStatusInfo = [
            ['lastPrice' => '1', 'openPrice' => '1'],
            ['lastPrice' => '2', 'openPrice' => '1'],
            ['lastPrice' => '3', 'openPrice' => '1'],
            ['lastPrice' => '1', 'openPrice' => '1'],
        ];

        $marketStatuses = [
            Symbols::WEB => $this->mockMarketStatus($markets[Symbols::WEB], $marketStatusInfo[0]),
            Symbols::BTC => $this->mockMarketStatus($markets[Symbols::BTC], $marketStatusInfo[1]),
            Symbols::BNB => $this->mockMarketStatus($markets[Symbols::BNB], $marketStatusInfo[2]),
            Symbols::ETH => $this->mockMarketStatus($markets[Symbols::ETH], $marketStatusInfo[3]),
        ];
        
        $marketStatusRepository->method('findByBaseQuoteNames')
            ->willReturnCallback(fn(string $base) => $marketStatuses[$base]);

        $cryptoRatesFetcher->method('fetch')
            ->willReturnOnConsecutiveCalls([
                Symbols::WEB => ["USD" => '1'],
                Symbols::BTC => ["USD" => '1'],
                Symbols::BNB => ["USD" => '1'],
                Symbols::ETH => ["USD" => '1'],
            ]);

        $manager = new MarketStatusManager(
            $this->mockEntityManager(),
            $marketStatusRepository,
            $this->mockMarketNameConverter(),
            $this->mockCryptoManager(),
            $this->mockMarketFactory(),
            $this->mockMarketHandler(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $cryptoRatesFetcher,
            $this->mockMarketPairsConfig(),
            $this->createMock(HideFeaturesConfig::class),
            $this->createMock(DisabledServicesConfig::class),
            $this->createMock(RebrandingConverterInterface::class)
        );

        $highestPrice = $manager->getTokenHighestPrice($markets);

        $this->assertEquals(
            [
                'symbol' => Symbols::BNB,
                'value' => '3',
                'subunit' => 4,
                'valueInUsd' => '3',
            ],
            [
                'symbol' => $highestPrice->getSymbol(),
                'value' => $highestPrice->getValue(),
                'subunit' => $highestPrice->getSubunit(),
                'valueInUsd'=>$highestPrice->getValueInUsd(),
            ],
        );
    }

    private function createMoney(string $value, string $currency): Money
    {
        return new Money($value, new Currency($currency));
    }

    /** @return MockObject|MarketStatus */
    private function mockMarketStatus(?Market $market = null, array $data = []): MarketStatus
    {
        $marketStatus = $this->createMock(MarketStatus::class);

        $market = $market ?? $this->mockMarket(Symbols::BTC, Symbols::WEB);

        if ($data['lastPrice']) {
            $marketStatus->method('getLastPrice')
                ->willReturn($this->createMoney($data['lastPrice'], $market->getBase()->getMoneySymbol()));
        }

        if ($data['openPrice']) {
            $marketStatus->method('getOpenPrice')
                ->willReturn($this->createMoney($data['openPrice'], $market->getBase()->getMoneySymbol()));
        }

        return $marketStatus;
    }

    /** @return MockObject|Market */
    private function mockMarket(string $base, string $quote, bool $isToken = false): Market
    {
        $baseMock = $this->mockCrypto($base);
        $quoteMock = $isToken
            ? $this->mockToken()
            : $this->mockCrypto($quote);

        $market =  $this->createMock(Market::class);

        $market->method('getBase')
            ->willReturn($baseMock);

        $market->method('getQuote')
            ->willReturn($quoteMock);

        return $market;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')
            ->willReturn($symbol);

        $crypto->method('getMoneySymbol')
            ->willReturn($symbol);

        $crypto->method('getShowSubunit')
            ->willReturn(4);

        return $crypto;
    }

    /** @return MockObject|Token */
    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getShowSubunit')
            ->willReturn(4);

        return $token;
    }

    /** @return MockObject|EntityManagerInterface */
    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return MockObject|MarketStatusRepository */
    private function mockMarketStatusRepository(): MarketStatusRepository
    {
        return $this->createMock(MarketStatusRepository::class);
    }

    /** @return MockObject|MarketNameConverterInterface */
    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        return $this->createMock(MarketNameConverterInterface::class);
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(): CryptoManagerInterface
    {
        return $this->createMock(CryptoManagerInterface::class);
    }

    /** @return MockObject|MarketFactoryInterface */
    private function mockMarketFactory(): MarketFactoryInterface
    {
        return $this->createMock(MarketFactoryInterface::class);
    }

    /** @return MockObject|MarketHandlerInterface */
    private function mockMarketHandler(): MarketHandlerInterface
    {
        return $this->createMock(MarketHandlerInterface::class);
    }

    /** @return MockObject|EventDispatcherInterface */
    private function mockEventDispatcher(): EventDispatcherInterface
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /** @return MockObject|MoneyWrapperInterface */
    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        $moneyWrapper->method('format')
            ->willReturnCallback(fn(Money $money) => $money->getAmount());

        return $moneyWrapper;
    }

    /** @return MockObject|CryptoRatesFetcherInterface */
    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        return $this->createMock(CryptoRatesFetcherInterface::class);
    }

    private function mockMarketPairsConfig(): MarketPairsConfig
    {
        return $this->createMock(MarketPairsConfig::class);
    }
}
