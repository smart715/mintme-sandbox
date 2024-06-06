<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Entity\Crypto;
use App\Exception\CryptoCalculatorException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Utils\CryptoCalculator;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class CryptoCalculatorTest extends TestCase
{
    private const WEB = "WEB";
    private const ETH = "ETH";

    /**
     * @dataProvider mintmeWorthProvider
     */
    public function testGetMintmeWorth(
        Money $cryptoAmount,
        Money $expectedMintmeAmount,
        array $orders,
        bool $notEnoughOrdersError,
        bool $expectException
    ): void {
        $cryptoConverter = new CryptoCalculator(
            $this->mockMoneyWrapper(),
            $this->mockMarketHandler($orders),
            $this->mockCryptoManager(),
            $this->mockMarketFactory()
        );

        if ($expectException) {
            $this->expectException(CryptoCalculatorException::class);
        }

        $mintmeAmount = $cryptoConverter->getMintmeWorth($cryptoAmount, $notEnoughOrdersError);

        $this->assertEquals($expectedMintmeAmount, $mintmeAmount);
    }

    public function mintmeWorthProvider(): array
    {
        return [
            '1 big order' => [
                new Money("20", new Currency(self::ETH)),
                new Money("10", new Currency(self::WEB)),
                [
                    $this->mockOrder("99999", "2"),
                ],
                false,
                false,
            ],
            '1 small order' => [
                new Money("20", new Currency(self::ETH)),
                new Money("5", new Currency(self::WEB)),
                [
                    $this->mockOrder("5", "2"),
                ],
                false,
                false,
            ],
            'many orders' => [
                new Money("20", new Currency(self::ETH)),
                new Money("7", new Currency(self::WEB)),
                [
                    $this->mockOrder("5", "2"),
                    $this->mockOrder("100", "5"),
                ],
                false,
                false,
            ],
            'enough orders + notEnoughOrdersException is enabled => no exception' => [
                new Money("20", new Currency(self::ETH)),
                new Money("10", new Currency(self::WEB)),
                [
                    $this->mockOrder("99999", "2"),
                ],
                true,
                false,
            ],
            'not enough orders + notEnoughOrdersException is enabled => exception' => [
                new Money("20", new Currency(self::ETH)),
                new Money("10", new Currency(self::WEB)),
                [
                    $this->mockOrder("1", "2"),
                ],
                true,
                true,
            ],
        ];
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method("parse")
            ->willReturnCallback(function (string $amount, string $symbol): Money {
                return new Money($amount, new Currency($symbol));
            });

        $moneyWrapper
            ->method("format")
            ->willReturnCallback(function (Money $amount): string {
                return $amount->getAmount();
            });

        return $moneyWrapper;
    }

    private function mockMarketHandler(array $orders): MarketHandlerInterface
    {
        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->method("getAllPendingSellOrders")
            ->willReturn($orders);

        return $marketHandler;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method("findBySymbol")
            ->willReturnCallback(function (string $symbol): ?Crypto {
                if (self::WEB === $symbol || self::ETH === $symbol) {
                    return $this->mockCrypto($symbol);
                }

                return null;
            });

        return $cryptoManager;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory
            ->method("create")
            ->willReturnCallback(function (Crypto $base, Crypto $quote): ?Market {
                if (self::ETH === $base->getSymbol() && self::WEB === $quote->getSymbol()) {
                    return $this->mockMarket();
                }

                return null;
            });

        return $marketFactory;
    }

    private function mockOrder(string $amount, string $price): Order
    {
        $order = $this->createMock(Order::class);
        $order
            ->method("getAmount")
            ->willReturn(new Money($amount, new Currency(self::WEB)));
        $order
            ->method("getPrice")
            ->willReturn(new Money($price, new Currency(self::ETH)));

        return $order;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto
            ->method("getSymbol")
            ->willReturn($symbol);

        return $crypto;
    }

    private function mockMarket(): Market
    {
        return $this->createMock(Market::class);
    }
}
