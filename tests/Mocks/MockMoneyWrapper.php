<?php declare(strict_types = 1);

namespace App\Tests\Mocks;

use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Converter;
use Money\Currencies;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;

trait MockMoneyWrapper
{
    /**
     * Returns a test double for the specified class.
     *
     * @param mixed $originalClassName
     * @return MockObject|mixed
     * @throws Exception
     */
    abstract protected function createMock($originalClassName);

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('parse')->willReturnCallback(function (string $amount, string $symbol) {
            return new Money(
                is_numeric($amount) ? (int)$amount : $amount,
                new Currency($symbol)
            );
        });

        $wrapper->method('format')->willReturnCallback(function (Money $money) {
            return $money->getAmount();
        });

        $wrapper->method('convert')->willReturnCallback(function (Money $money, Currency $currency, FixedExchange $exchange) {
            $currencyPair = $exchange->quote(
                new Currency($money->getCurrency()->getCode()),
                new Currency($currency->getCode())
            );

            $ratio = $currencyPair->getConversionRatio();

            return $money->multiply($ratio);
        });

        $wrapper->method('convertByRatio')->willReturnCallback(function (
            Money $amount,
            string $toCurrency,
            string $ratio
        ) {
            $from = $amount->getCurrency()->getCode();

            $exchange = new FixedExchange([
                $from => [
                    $toCurrency => $ratio,
                ],
            ]);
            $converter = new Converter($this->createMock(Currencies::class), $exchange);

            return $converter->convert($amount, new Currency($toCurrency));
        });

        $wrapper->method('convertToDecimalIfNotation')->willReturnCallback(function (string $val) {
            $regEx = '/^(?<left> (?P<sign> [+\-]?) 0*(?P<mantissa> [0-9]+(?P<decimals> \.[0-9]+)?) )[eE] (?<right> (?P<expSign> [+\-]?)(?P<exp> \d+))$/x';

            if (preg_match($regEx, $val, $matches)) {
                $scale = 8;

                $power = $matches['right'] < 0
                    ? BigDecimal::one()->exactlyDividedBy(
                        BigDecimal::of(10)->power(-(int)$matches['right'])
                    )
                    : BigDecimal::of(10)->power((int)$matches['right']);

                return (string) BigDecimal::of($matches['left'])
                    ->multipliedBy($power)
                    ->toScale($scale, RoundingMode::DOWN);
            }

            return str_replace(' ', '', $val);
        });

        return $wrapper;
    }
}
