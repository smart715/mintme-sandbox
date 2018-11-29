<?php

namespace App\Wallet\Money;

use Money\Currencies;
use Money\Money;

interface MoneyWrapperInterface
{
    public function getRepository(): Currencies;
    public function format(Money $money): string;
    public function getBase(string $value, string $symbol): Money;
    public function parse(string $value, string $symbol): Money;
}