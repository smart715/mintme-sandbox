<?php declare(strict_types = 1);

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

    private function convertToDecimalIfNotation(string $notation, string $symbol): string
    {
        $regEx = '/^(?<left> (?P<sign> [+\-]?) 0*(?P<mantissa> [0-9]+(?P<decimals> \.[0-9]+)?) ) [eE] (?<right> (?P<expSign> [+\-]?)(?P<exp> \d+))$/x';
        if (preg_match($regEx, $notation, $matches)) {
            bcscale($this->getRepository()->subunitFor(new Currency($symbol)));
            return bcmul($matches['left'], bcpow('10', $matches['right']));
        }
        return $notation;
    }

    public function parse(string $value, string $symbol): Money
    {
        return (new DecimalMoneyParser($this->getRepository()))->parse(
            $this->convertToDecimalIfNotation($value, $symbol),
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
