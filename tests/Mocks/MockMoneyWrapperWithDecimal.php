<?php declare(strict_types = 1);

namespace App\Tests\Mocks;

use App\Wallet\Money\MoneyWrapperInterface;
use Money\Converter;
use Money\Currencies;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;

trait MockMoneyWrapperWithDecimal
{
    /**
     * Returns a test double for the specified class.
     *
     * @param mixed $originalClassName
     * @return MockObject|mixed
     * @throws Exception
     */
    abstract protected function createMock($originalClassName);

    private function mockMoneyWrapperWithDecimal(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('parse')->willReturnCallback(function (string $decimal, string $symbol) {
            $decimalSeparator = strpos($decimal, '.');
            $subunit = 'TOK' === $symbol
                ? 12
                : 18;

            if (false !== $decimalSeparator) {
                $decimal = rtrim($decimal, '0');
                $lengthDecimal = strlen($decimal);
                $decimal = str_replace('.', '', $decimal);
                $decimal .= str_pad('', ($lengthDecimal - $decimalSeparator - $subunit - 1) * -1, '0');
            } else {
                $decimal .= str_pad('', $subunit, '0');
            }

            $decimal = '-' === $decimal[0]
                ? '-' . ltrim(substr($decimal, 1), '0')
                : ltrim($decimal, '0');

            if ('' === $decimal) {
                $decimal = '0';
            }

            return new Money(
                $decimal,
                new Currency($symbol)
            );
        });

        $wrapper->method('format')->willReturnCallback(function (Money $money) {
            $valueBase = $money->getAmount();
            $negative = false;

            if ('-' === $valueBase[0]) {
                $negative = true;
                $valueBase = substr($valueBase, 1);
            }

            $subunit = 'TOK' === $money->getCurrency()->getCode()
                ? 12
                : 18;
            $valueLength = strlen($valueBase);

            if ($valueLength > $subunit) {
                $formatted = substr($valueBase, 0, $valueLength - $subunit);
                $decimalDigits = substr($valueBase, $valueLength - $subunit);

                if ('' !== $decimalDigits) {
                    $formatted .= '.'.$decimalDigits;
                }
            } else {
                $formatted = '0.'.str_pad('', $subunit - $valueLength, '0').$valueBase;
            }

            if (true === $negative) {
                $formatted = '-'.$formatted;
            }

            return $formatted;
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
            $converter = new Converter($this->mockCurrencies(), $exchange);

            return $converter->convert($amount, new Currency($toCurrency));
        });

        return $wrapper;
    }

    private function mockCurrencies(): Currencies
    {
        $currencies = $this->createMock(Currencies::class);
        $currencies->method('subunitFor')->willReturnCallback(function (Currency $currency) {
            return 'TOK' === $currency->getCode()
                ? 12
                : 18;
        });

        return $currencies;
    }
}
