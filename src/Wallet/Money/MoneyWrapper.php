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
        return number_format(floatval($notation), 0, '', '');
    }

    public function parse(string $value, string $symbol): Money
    {
        return (new DecimalMoneyParser($this->getRepository()))->parse(
            $this->convertToDecimalIfNotation($value),
            $symbol
        );
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
