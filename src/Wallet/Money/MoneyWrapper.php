<?php declare(strict_types = 1);

namespace App\Wallet\Money;

use App\Manager\CryptoManagerInterface;
use Money\Converter;
use Money\Currencies;
use Money\Currencies\CurrencyList;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

final class MoneyWrapper implements MoneyWrapperInterface
{
    public const TOK_SYMBOL = 'TOK';
    private const TOK_SUBUNIT = 12;
    public const USD_SYMBOL = 'USD';
    private const USD_SUBUNIT = 0;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var CurrencyList */
    private $currencies;

    /** @var DecimalMoneyFormatter */
    private $formatter;

    /** @var DecimalMoneyParser */
    private $parser;

    /** @var Converter */
    private $converter;

    public function __construct(
        CryptoManagerInterface $cryptoManager
    ) {
        $this->cryptoManager = $cryptoManager;

        $this->currencies = new CurrencyList(
            array_merge(
                $this->fetchCurrencies(),
                [
                    self::TOK_SYMBOL => self::TOK_SUBUNIT,
                    self::USD_SYMBOL => self::USD_SUBUNIT,
                ]
            )
        );

        $this->formatter = new DecimalMoneyFormatter($this->currencies);
        $this->parser = new DecimalMoneyParser($this->currencies);
    }

    public function getRepository(): Currencies
    {
        return $this->currencies;
    }

    public function format(Money $money): string
    {
        return $this->formatter->format($money);
    }

    public function convertToDecimalIfNotation(string $notation, string $symbol): string
    {
        $regEx = '/^(?<left> (?P<sign> [+\-]?) 0*(?P<mantissa> [0-9]+(?P<decimals> \.[0-9]+)?) ) [eE] (?<right> (?P<expSign> [+\-]?)(?P<exp> \d+))$/x';

        if (preg_match($regEx, $notation, $matches)) {
            bcscale($this->currencies->subunitFor(new Currency($symbol)));

            return bcmul($matches['left'], bcpow('10', $matches['right']));
        }

        return $notation;
    }

    public function parse(string $value, string $symbol): Money
    {
        return $this->parser->parse(
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

    public function convert(Money $money, Currency $currency, ?FixedExchange $exchange = null): Money
    {
        if (null !== $exchange) {
            $this->converter = new Converter($this->currencies, $exchange);
        } elseif (!isset($this->converter)) {
            throw new \Exception('You can only omit parameter $exchange if you already passed it on a previous call to method MoneyWrapper::convert');
        }

        return $this->converter->convert($money, $currency);
    }
}
