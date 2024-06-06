<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Market;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\RestRpcInterface;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Market\MarketCapCalculator;
use App\Manager\CryptoManagerInterface;
use App\Repository\MarketStatusRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currencies;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MarketCapCalculatorTest extends TestCase
{
    # Just to please phpstan
    /** @var FixedExchange */
    private $exchange;

    public function testCalculate(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);

        # Tokens
        $token = $this->mockToken();

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');
        $tokenweb = $this->mockMarketStatus($token, $web, '10', '0');

        # Market Status Repository

        $repo = $this->mockMarketStatusRepository([$tokenweb], [$webbtc], $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            0
        );

        $this->assertEquals('11000', $marketCapCalculator->calculate());
    }

    public function testCalculateWithNotDeployed(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);

        # Tokens
        $token = $this->mockToken(false);

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');
        $tokenweb = $this->mockMarketStatus($token, $web, '10', '0');

        # Market Status Repository

        $repo = $this->mockMarketStatusRepository([$tokenweb], [$webbtc], $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            0
        );

        $this->assertEquals('1000', $marketCapCalculator->calculate());
    }

    public function testCalculateWithMoreMarkets(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);
        # Just for testing sake
        $ethereum = $this->mockCrypto('ETH', 'Ethereum', false);
        $monero = $this->mockCrypto('XMR', 'Monero', false);

        # Tokens
        $token = $this->mockToken();

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');

        $exchangeableCryptoMarkets = [
            $webbtc,
            $this->mockMarketStatus($ethereum, $btc, '7', '0'),
            $this->mockMarketStatus($monero, $btc, '1', '0'),
        ];

        $tokenWEBMarkets = [
            $this->mockMarketStatus($token, $web, '15', '0'),
            $this->mockMarketStatus($token, $web, '37', '0'),
            $this->mockMarketStatus($token, $web, '42', '0'),
            $this->mockMarketStatus($token, $web, '55', '0'),
            $this->mockMarketStatus($token, $web, '5', '0'),
            $this->mockMarketStatus($token, $web, '8', '0'),
            $this->mockMarketStatus($token, $web, '4', '0'),
            $this->mockMarketStatus($token, $web, '100', '0'),
            $this->mockMarketStatus($token, $web, '63', '0'),
            $this->mockMarketStatus($token, $web, '82', '0'),
        ];

        # Market Status Repository
        $repo = $this->mockMarketStatusRepository($tokenWEBMarkets, $exchangeableCryptoMarkets, $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            0
        );

        $this->assertEquals('412800', $marketCapCalculator->calculate());
    }

    public function testCalculateInUSD(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);

        # Tokens
        $token = $this->mockToken();

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');
        $tokenweb = $this->mockMarketStatus($token, $web, '10', '0');

        # Market Status Repository

        $repo = $this->mockMarketStatusRepository([$tokenweb], [$webbtc], $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            0
        );

        $this->assertEquals('20000', $marketCapCalculator->calculate('USD'));
    }

    public function testCalculateWithInvalidBase(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);

        # Tokens
        $token = $this->mockToken();

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');
        $tokenweb = $this->mockMarketStatus($token, $web, '10', '0');

        # Market Status Repository
        $repo = $this->mockMarketStatusRepository([$tokenweb], [$webbtc], $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            0
        );

        $this->expectException(\Throwable::class);

        $marketCapCalculator->calculate('ETH');
    }

    public function testCalculateWithUnreliableMarkets(): void
    {
        # Cryptos
        $web = $this->mockCrypto('WEB', 'Webchain', true);
        $btc = $this->mockCrypto('BTC', 'Bitcoin', true);

        # Tokens
        $token = $this->mockToken();

        # Markets
        $webbtc = $this->mockMarketStatus($web, $btc, '10', '0');
        $tokenweb = $this->mockMarketStatus($token, $web, '10', '10');

        # Market Status Repository
        $repo = $this->mockMarketStatusRepository([$tokenweb], [], $webbtc);

        $marketCapCalculator = new MarketCapCalculator(
            ['Webchain' => __DIR__.'/supply.txt'],
            100,
            $this->mockEntityManager($repo),
            $this->mockMoneyWrapper(0),
            $this->mockRpc(),
            $this->mockCryptoRatesFetcher(),
            $this->createMock(LoggerInterface::class),
            100
        );

        $this->assertEquals('0', $marketCapCalculator->calculate());
    }

    private function mockEntityManager(MarketStatusRepository $repo): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(MarketStatus::class)->willReturn($repo);

        return $em;
    }

    private function mockMarketStatusRepository(
        array $tokenWEBMarkets,
        array $exchangeableCryptoMarkets,
        MarketStatus $webbase
    ): MarketStatusRepository {
        $repo = $this->createMock(MarketStatusRepository::class);
        $repo->method('getTokenWEBMarkets')->willReturn($tokenWEBMarkets);
        $repo->method('getExchangeableCryptoMarkets')->willReturn($exchangeableCryptoMarkets);
        $repo->method('findByBaseQuoteNames')
            ->with(
                $webbase->getCrypto()->getSymbol(),
                $webbase->getQuote()->getSymbol()
            )
            ->willReturn($webbase);

        return $repo;
    }

    /**
        @param Crypto|Token $quote
    */
    private function mockMarketStatus(
        $quote,
        Crypto $crypto,
        string $lastPrice,
        string $monthVolume
    ): MarketStatus {
        $lastPrice = new Money($lastPrice, new Currency($crypto->getSymbol()));
        $monthVolume = new Money($monthVolume, new Currency($crypto->getSymbol()));

        $ms = $this->createMock(MarketStatus::class);
        $ms->method('getLastPrice')->willReturn($lastPrice);
        $ms->method('getCrypto')->willReturn($crypto);
        $ms->method('getQuote')->willReturn($quote);
        $ms->method('getMonthVolume')->willReturn($monthVolume);

        return $ms;
    }

    private function mockCrypto(string $symbol, string $name, bool $tradable): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getName')->willReturn($name);
        $crypto->method('isTradable')->willReturn($tradable);

        return $crypto;
    }

    private function mockToken(bool $deployed = true): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getDeployed')->willReturn($deployed);

        return $token;
    }

    private function mockMoneyWrapper(int $minimumVolumeForMarketcap): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);

        $callback = function ($money, $currency, $exchange) {
            if (null !== $exchange) {
                $this->exchange = $exchange;
            }

            return (new Money($money->getAmount(), $currency))
                ->multiply(
                    $this->exchange->quote($money->getCurrency(), $currency)->getConversionRatio()
                );
        };

        $mw->method('convert')->willReturnCallback($callback->bindTo($mw));
        $mw->method('format')->willReturnCallback(function ($money) {
            return $money->getAmount();
        });

        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol) {
            return new Money($amount, new Currency($symbol));
        });

        return $mw;
    }

    private function mockRpc(): RestRpcInterface
    {
        $rpc = $this->createMock(RestRpcInterface::class);
        $rpc->method('send')->willReturnCallback(function ($url) {
            if (preg_match('/coins\/markets/', $url)) {
                $matches = [];
                preg_match('/(?<=ids=).+(?=&order)/', $url, $matches);

                $cryptos = explode(',', $matches[0]);

                return json_encode(array_map(function ($name) {
                    return [
                        'name' => ucfirst($name),
                        'circulating_supply' => 100,
                    ];
                }, $cryptos));
            }
        });

        return $rpc;
    }

    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $crf = $this->createMock(CryptoRatesFetcherInterface::class);

        $crf->method('fetch')->willReturn([
            'WEB' => [
                'USD' => 10,
                'BTC' => 10,
            ],
            'BTC' => [
                'USD' => 10,
                'BTC' => 1,
            ],
        ]);

        return $crf;
    }
}
