<?php declare(strict_types = 1);

namespace App\Wallet\Money;

use Money\Currencies;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

interface MoneyWrapperInterface
{
    public function getRepository(): Currencies;
    public function format(Money $money, bool $trailingZeros = true): string;
    public function parse(string $value, string $symbol): Money;
    public function convertToDecimalIfNotation(string $notation, string $symbol): string;
    public function convert(Money $money, Currency $currency, ?FixedExchange $exchange = null): Money;
    public function convertByRatio(Money $amount, string $toCurrency, string $ratio): Money;

    /**
     * Converts amount depends on subunit change.
     * It's necessary when we work with the same crypto, but different subunits.
     */
    public function convertAmountSubunits(Money $moneyToConvert, int $subunitChange): Money;
}
