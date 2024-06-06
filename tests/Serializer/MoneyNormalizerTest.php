<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Serializer\MoneyNormalizer;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyNormalizerTest extends TestCase
{
    public function testNormalizeWithARegisteredCurrency(): void
    {
        $normalizer = new MoneyNormalizer($this->mockMoneyWrapper(true));

        $money = $this->createMoney('1', 'TEST');

        $this->assertEquals(
            '1 TEST',
            $normalizer->normalize($money)
        );
    }

    public function testNormalizeWithAnUnregisteredCurrency(): void
    {
        $normalizer = new MoneyNormalizer($this->mockMoneyWrapper(false));

        $money = $this->createMoney('1', 'TEST');

        $this->assertEquals(
            '1 TOK',
            $normalizer->normalize($money)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new MoneyNormalizer($this->mockMoneyWrapper());

        $money = $this->createMoney('1', 'TOK');

        $this->assertTrue($normalizer->supportsNormalization($money));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new MoneyNormalizer($this->mockMoneyWrapper());

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function createMoney(string $amount, string $symbol): Money
    {
        return new Money($amount, new Currency($symbol));
    }

    private function mockMoneyWrapper(bool $isCurrencyExist = false): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('getRepository')
            ->willReturn($this->mockCurrencies($isCurrencyExist));
        $moneyWrapper->method('format')
            ->willReturnCallback(function (Money $money) {
                return $money->getAmount() . ' ' . $money->getCurrency()->getCode();
            });

        return $moneyWrapper;
    }

    private function mockCurrencies(bool $isCurrencyExist): Currencies
    {
        $currencies = $this->createMock(Currencies::class);
        $currencies->method('contains')
            ->willReturn($isCurrencyExist);

        return $currencies;
    }
}
