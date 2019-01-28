<?php

namespace App\Wallet\Money;

use App\Manager\CryptoManagerInterface;
use Money\Currencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

final class MoneyWrapper implements MoneyWrapperInterface
{
    public const TOK_SYMBOL = 'TOK';
    private const TOK_SUBUNIT = 12;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager
    ) {
        $this->cryptoManager = $cryptoManager;
    }

    public function getRepository(): Currencies
    {
        return new Currencies\CurrencyList(
            array_merge($this->fetchCurrencies(), [ 'TOK' => self::TOK_SUBUNIT ])
        );
    }

    public function format(Money $money): string
    {
        return (new DecimalMoneyFormatter($this->getRepository()))->format($money);
    }

    public function convertToDecimalIfNotation(string $notation): string
    {
        if (preg_match('/^(?<sign>[-]?)(?<number>\d+)e(?<direction>[-]?)(?<point>\d+)$/u', $notation, $matches)) {
            $number = $matches['number'];
            $point = $matches['point'];
            $sign = $matches['sign'];
            $direction = $matches['direction'];
            $zerosCount = $direction
                ? $point - strlen($number)
                : $point;
            $zeros = str_repeat('0', $zerosCount);
            return $direction
                ? $sign . '0.' . $zeros . $number
                : $sign . $number . $zeros . '.0';
        }
        return $notation;
    }

    public function parse(string $value, string $symbol): Money
    {
        $value = $this->convertToDecimalIfNotation($value);
        return (new DecimalMoneyParser($this->getRepository()))->parse($value, $symbol);
    }

    private function fetchCurrencies(): array
    {
        $currencies = [];

        foreach ($this->cryptoManager->findAll() as $crypto) {
            $currencies[$crypto->getSymbol()] = $crypto->getSubunit();
        }

        return $currencies;
    }
}
