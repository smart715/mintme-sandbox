<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Donation\DonationChecker;
use App\Exchange\Donation\DonationFetcherInterface;
use App\Exchange\Donation\Model\CheckDonationRawResult;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DonationCheckerTest extends TestCase
{
    private const MARKET_BASE = 'WEB';
    private const MARKET_QUOTE = 'TOK000000000001';
    private const QUOTE_CURRENCY_SYMBOL = 'TOK';
    private const CONVERTED_MARKET_NAME = 'WEB/TOK000000000001';
    private const FORMATTED_DONATION_AMOUNT = 'formatted donation amount';
    private const FEE_RATE = '0.007';
    private const TOKEN_CREATOR_ID = 1;

    /** @dataProvider getCheckOneWayDonation */
    public function testCheckOneWayDonation(
        Money $donationAmount,
        Money $expectedTokensAmount,
        Money $expectedTokensWorth
    ): void {
        $donationChecker = new DonationChecker(
            $this->mockOneWayDonationFetcher(
                $expectedTokensAmount->getAmount(),
                $expectedTokensWorth->getAmount()
            ),
            $this->mockMarketNameConverter(),
            $this->mockMoneyWrapper(),
            $this->mockQuickTradeConfig()
        );

        $market = $this->mockMarket();
        $tokenCreator = $this->mockTokenCreator();

        $donationCheckResult = $donationChecker->checkOneWayDonation(
            $market,
            $donationAmount,
            $tokenCreator
        );

        $actualTokensAmount = $donationCheckResult->getExpectedTokensAmount();
        $actualTokensWorth = $donationCheckResult->getExpectedTokensWorth();

        $this->assertEquals($expectedTokensAmount, $actualTokensAmount);
        $this->assertEquals($expectedTokensWorth, $actualTokensWorth);
    }

    /** @dataProvider getCheckTwoWayDonation */
    public function testCheckTwoWayDonation(
        Money $donationAmount,
        Money $expectedTokensAmountFirst,
        Money $expectedTokensWorthFirst,
        Money $expectedTokensAmountSecond,
        Money $expectedTokensWorthSecond
    ): void {
        $donationChecker = new DonationChecker(
            $this->mockTwoWayDonationFetcher(
                $expectedTokensAmountFirst->getAmount(),
                $expectedTokensWorthFirst->getAmount(),
                $expectedTokensAmountSecond->getAmount(),
                $expectedTokensWorthSecond->getAmount(),
            ),
            $this->mockMarketNameConverter(),
            $this->mockMoneyWrapper(),
            $this->mockQuickTradeConfig()
        );

        $market = $this->mockMarket();
        $tokenCreator = $this->mockTokenCreator();

        $donationCheckResult = $donationChecker->checkTwoWayDonation(
            $market,
            $donationAmount,
            $tokenCreator
        );

        $actualTokensAmount = $donationCheckResult->getExpectedTokensAmount();
        $actualTokensWorth = $donationCheckResult->getExpectedTokensWorth();

        $this->assertEquals($expectedTokensAmountSecond, $actualTokensAmount);
        $this->assertEquals($expectedTokensWorthFirst, $actualTokensWorth);
    }

    public function testDonationFetcherCheckDonationCall(): void
    {
        $donationChecker = new DonationChecker(
            $this->mockDonationFetcherAssertions(),
            $this->mockMarketNameConverter(),
            $this->mockMoneyWrapper(),
            $this->mockQuickTradeConfig()
        );

        $market = $this->mockMarket();
        $donationAmount = new Money('100', new Currency(self::MARKET_BASE));
        $tokenCreator = $this->mockTokenCreator();

        $donationChecker->checkOneWayDonation(
            $market,
            $donationAmount,
            $tokenCreator
        );
    }

    public function getCheckOneWayDonation(): array
    {
        $cryptoCurrency = new Currency(self::MARKET_BASE);
        $tokenCurrency = new Currency(self::QUOTE_CURRENCY_SYMBOL);

        return [
            [
                new Money('100', $cryptoCurrency),
                new Money('100', $tokenCurrency),
                new Money('100', $cryptoCurrency),
            ],
            [
                new Money('100', $cryptoCurrency),
                new Money('99', $tokenCurrency),
                new Money('99', $cryptoCurrency),
            ],
            [
                new Money('100', $cryptoCurrency),
                new Money('50', $tokenCurrency),
                new Money('50', $cryptoCurrency),
            ],
        ];
    }

    public function getCheckTwoWayDonation(): array
    {
        $cryptoCurrency = new Currency(self::MARKET_BASE);
        $tokenCurrency = new Currency(self::QUOTE_CURRENCY_SYMBOL);

        return [
            [
                new Money('100', $cryptoCurrency),
                new Money('100', $tokenCurrency),
                new Money('100', $cryptoCurrency),
                new Money('100', $tokenCurrency),
                new Money('100', $cryptoCurrency),
            ],
            [
                new Money('100', $cryptoCurrency),
                new Money('99', $tokenCurrency),
                new Money('99', $cryptoCurrency),
                new Money('99', $tokenCurrency),
                new Money('99', $cryptoCurrency),
            ],
            [
                new Money('100', $cryptoCurrency),
                new Money('99', $tokenCurrency),
                new Money('99', $cryptoCurrency),
                new Money('98', $tokenCurrency),
                new Money('98', $cryptoCurrency),
            ],
            [
                new Money('100', $cryptoCurrency),
                new Money('50', $tokenCurrency),
                new Money('50', $cryptoCurrency),
                new Money('49', $tokenCurrency),
                new Money('49', $cryptoCurrency),
            ],
        ];
    }

    private function mockOneWayDonationFetcher(
        string $expectedTokensAmount,
        string $expectedTokensWorth
    ): DonationFetcherInterface {
        $donationFetcher = $this->createMock(DonationFetcherInterface::class);
        $donationFetcher
            ->expects($this->once())
            ->method('checkDonation')
            ->willReturn(new CheckDonationRawResult($expectedTokensAmount, $expectedTokensWorth));

        return $donationFetcher;
    }

    private function mockTwoWayDonationFetcher(
        string $expectedTokensAmountFirst,
        string $expectedTokensWorthFirst,
        string $expectedTokensAmountSecond,
        string $expectedTokensWorthSecond
    ): DonationFetcherInterface {
        $donationFetcher = $this->createMock(DonationFetcherInterface::class);
        $donationFetcher
            ->expects($this->exactly(2))
            ->method('checkDonation')
            ->willReturnOnConsecutiveCalls(
                new CheckDonationRawResult($expectedTokensAmountFirst, $expectedTokensWorthFirst),
                new CheckDonationRawResult($expectedTokensAmountSecond, $expectedTokensWorthSecond)
            );

        return $donationFetcher;
    }
    private function mockDonationFetcherAssertions(): DonationFetcherInterface
    {
        $donationFetcher = $this->createMock(DonationFetcherInterface::class);
        $donationFetcher
            ->expects($this->once())
            ->method('checkDonation')
            ->willReturnCallback(
                function (
                    string $marketName,
                    string $donationAmount,
                    string $feeRate,
                    int $tokenCreatorId
                ): CheckDonationRawResult {
                    $this->assertEquals(self::CONVERTED_MARKET_NAME, $marketName);
                    $this->assertEquals(self::FORMATTED_DONATION_AMOUNT, $donationAmount);
                    $this->assertEquals(self::FEE_RATE, $feeRate);
                    $this->assertEquals(self::TOKEN_CREATOR_ID, $tokenCreatorId);

                    return new CheckDonationRawResult('100', '100');
                }
            );

        return $donationFetcher;
    }

    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter->method('convert')->willReturn(self::CONVERTED_MARKET_NAME);

        return $marketNameConverter;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('format')
            ->willReturn(self::FORMATTED_DONATION_AMOUNT);
        $moneyWrapper
            ->method('parse')
            ->willReturnCallback(function (string $amount, string $symbol): Money {
                return new Money($amount, new Currency($symbol));
            });

        return $moneyWrapper;
    }

    private function mockQuickTradeConfig(): QuickTradeConfig
    {
        $quickTradeConfig = $this->createMock(QuickTradeConfig::class);
        $quickTradeConfig->method('getBuyTokenFee')->willReturn(self::FEE_RATE);

        return $quickTradeConfig;
    }

    private function mockMarket(): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getBase')->willReturn($this->mockTradable(self::MARKET_BASE));
        $market->method('getQuote')->willReturn($this->mockTradable(self::MARKET_QUOTE));

        return $market;
    }

    private function mockTradable(string $symbol): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getSymbol')->willReturn($symbol);

        return $tradable;
    }

    private function mockTokenCreator(): User
    {
        $tokenCreator = $this->createMock(User::class);
        $tokenCreator->method('getId')->willReturn(self::TOKEN_CREATOR_ID);

        return $tokenCreator;
    }
}
