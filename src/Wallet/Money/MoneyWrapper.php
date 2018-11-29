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

    public function parse(string $value, string $symbol): Money
    {
        return (new DecimalMoneyParser($this->getRepository()))->parse($value, $symbol);
    }

    public function getBase(string $value, string $symbol): Money
    {
        $currency = new Currency($symbol);

        return new Money(
            bcmul($value, bcpow('10', (string)$this->getRepository()->subunitFor($currency))),
            $currency
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