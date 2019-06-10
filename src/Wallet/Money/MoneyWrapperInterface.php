<?php declare(strict_types = 1);

namespace App\Wallet\Money;

use Money\Currencies;
use Money\Money;

interface MoneyWrapperInterface
{
    public function getRepository(): Currencies;
    public function format(Money $money): string;
    public function parse(string $value, string $symbol): Money;
    public function convertToDecimalIfNotation(string $notation, string $symbol): string;
}
