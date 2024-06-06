<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\TradableInterface;
use App\Exchange\Order;
use App\Services\TranslatorService\TranslatorInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Validator\OrderMinUsdValidator;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class OrderMinUsdValidatorTest extends TestCase
{

    use mockMoneyWrapper;

    /** @dataProvider casesProvider */
    public function testValid(
        string $minimum,
        string $price,
        string $amount,
        bool $isValid,
        bool $isBuyOrder = false,
        array $buyOrderbook = []
    ): void {
        $validator = new OrderMinUsdValidator(
            $this->mockTradable(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoRatesFetcher(),
            $this->mockTranslator(),
            $this->mockRebrandingConverter(),
            $minimum,
            $this->createMoney($price),
            $this->createMoney($amount),
            $isBuyOrder,
            $buyOrderbook
        );

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('test', $validator->getMessage());
    }

    public function casesProvider(): array
    {
        return [
            'sell order total price is more than minimum is valid' => [
                'minimum' => '100',
                'price' => '101',
                'amount' => '1',
                'isValid' => true,
                'isBuyOrder' => false,
            ],
            'sell order total price is less than minimum is not valid' => [
                'minimum' => '100',
                'price' => '99',
                'amount' => '1',
                'isValid' => false,
                'isBuyOrder' => false,
            ],
            'sell order total price is equal to minimum is valid' => [
                'minimum' => '100',
                'price' => '100',
                'amount' => '1',
                'isValid' => true,
                'isBuyOrder' => false,
            ],
            'buy order total price is more than minimum is valid' => [
                'minimum' => '100',
                'price' => '101',
                'amount' => '1',
                'isValid' => true,
                'isBuyOrder' => true,
            ],
            'buy order total price is less than minimum is not valid' => [
                'minimum' => '100',
                'price' => '99',
                'amount' => '1',
                'isValid' => false,
                'isBuyOrder' => true,
            ],
            'buy order total price is equal to minimum is valid' => [
                'minimum' => '100',
                'price' => '100',
                'amount' => '1',
                'isValid' => true,
                'isBuyOrder' => true,
            ],
            'buy order with total price higher than minimum but sell order make it lower is invalid' => [
                'minimum' => '100',
                'price' => '100',
                'amount' => '1',
                'isValid' => false,
                'isBuyOrder' => true,
                'orderbook' => [
                    $this->mockOrder('99', '1'),
                ],
            ],
            'buy order with total price equal than minimum but 2 sell order make it lower is invalid' => [
                'minimum' => '300',
                'price' => '100',
                'amount' => '3',
                'isValid' => false,
                'isBuyOrder' => true,
                'orderbook' => [
                    $this->mockOrder('99', '1'),
                    $this->mockOrder('98', '1'),
                ],
            ],
            'buy order with total price higher enough than minimum but 2 sell order make it lower is valid' => [
                'minimum' => '300',
                'price' => '103',
                'amount' => '3',
                'isValid' => true,
                'isBuyOrder' => true,
                'orderbook' => [
                    $this->mockOrder('99', '1'),
                    $this->mockOrder('98', '1'),
                ],
            ],
            'buy order with total price equal than minimum and orderbook make it higher or equal is valid' => [
                'minimum' => '100',
                'price' => '100',
                'amount' => '1',
                'isValid' => true,
                'isBuyOrder' => true,
                'orderbook' => [
                    $this->mockOrder('101', '1'),
                ],
            ],
        ];
    }

    private function mockTradable(): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getMoneySymbol')->willReturn('TEST');
        $tradable->method('getSymbol')->willReturn('TEST');

        return $tradable;
    }

    private function mockTranslator(): translatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->willReturn('test');

        return $translator;
    }

    private function mockRebrandingConverter(): RebrandingConverterInterface
    {
        $rebrandingConverter = $this->createMock(RebrandingConverterInterface::class);
        $rebrandingConverter->expects($this->once())
            ->method('convert');

        return $rebrandingConverter;
    }

    private function createMoney(string $amount): Money
    {
        return new Money(
            $amount,
            new Currency('TEST')
        );
    }

    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $cryptoRatesFetcher = $this->createMock(CryptoRatesFetcherInterface::class);
        $cryptoRatesFetcher->method('fetch')->willReturn(["TEST" => ["USD" => 1]]);

        return $cryptoRatesFetcher;
    }

    private function mockOrder(string $price, string $amount): Order
    {
        $order = $this->createMock(Order::class);
        $priceObject = $this->createMoney($price);
        $amountObject = $this->createMoney($amount);
        $order->method('getPrice')->willReturn($priceObject);
        $order->method('getAmount')->willReturn($amountObject);

        return $order;
    }
}
