<?php declare(strict_types = 1);

namespace App\Wallet\Money;

use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
    public const TOK_SUBUNIT = 12;
    public const USD_SUBUNIT = 2;
    public const MINTME_SUBUNIT = 18;
    public const MINTME_SHOW_SUBUNIT = 4;

    private CryptoManagerInterface $cryptoManager;

    private CurrencyList $currencies;

    private DecimalMoneyFormatter $formatter;

    private DecimalMoneyParser $parser;

    private Converter $converter;

    public function __construct(CryptoManagerInterface $cryptoManager)
    {
        $this->cryptoManager = $cryptoManager;
        $this->currencies = new CurrencyList(
            array_merge(
                $this->fetchCurrencies(),
                [
                    Symbols::TOK => self::TOK_SUBUNIT,
                    Symbols::USD => self::USD_SUBUNIT,
                ]
            )
        );
        $this->formatter = new DecimalMoneyFormatter($this->getRepository());
        $this->parser = new DecimalMoneyParser($this->getRepository());
    }

    public function getRepository(): Currencies
    {
        return $this->currencies;
    }

    public function format(Money $money, bool $trailingZeros = true): string
    {
        $formatted = $this->getFormatter()->format($money);

        if (!$trailingZeros) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }

    public function convertByRatio(Money $amount, string $toCurrency, string $ratio): Money
    {
        $from = $amount->getCurrency()->getCode();

        $exchange = new FixedExchange([
            $from => [
                $toCurrency => $ratio,
            ],
        ]);

        return $this->convert($amount, new Currency($toCurrency), $exchange);
    }

    public function convertToDecimalIfNotation(string $notation, string $symbol): string
    {
        $regEx = '/^(?<left> (?P<sign> [+\-]?) 0*(?P<mantissa> [0-9]+(?P<decimals> \.[0-9]+)?) )[eE] (?<right> (?P<expSign> [+\-]?)(?P<exp> \d+))$/x';

        if (preg_match($regEx, $notation, $matches)) {
            $scale = $this->getRepository()->subunitFor(new Currency($symbol));

            $power = $matches['right'] < 0
            ? BigDecimal::one()->exactlyDividedBy(
                BigDecimal::of(10)->power(-(int)$matches['right'])
            )
            : BigDecimal::of(10)->power((int)$matches['right']);

            return (string) BigDecimal::of($matches['left'])
                ->multipliedBy($power)
                ->toScale($scale, RoundingMode::DOWN);
        }

        $notation = str_replace(' ', '', $notation);

        return $notation;
    }

    public function parse(string $value, string $symbol): Money
    {
        $value = ltrim($value, '0') ?: '0';

        return $this->getParser()->parse(
            $this->convertToDecimalIfNotation($value, $symbol),
            $symbol
        );
    }

    private function fetchCurrencies(): array
    {
        $currencies = [];

        foreach ($this->cryptoManager->findSymbolAndSubunitArr() as $result) {
            $symbol = (string)$result['symbol'];
            $subunit = (int)$result['subunit'];

            $currencies[$symbol] = $subunit;

            if (Symbols::WEB === $symbol) {
                $currencies[Symbols::MINTME] = $subunit;
            }
        }

        return $currencies;
    }

    public function convert(Money $money, Currency $currency, ?FixedExchange $exchange = null): Money
    {
        if (null !== $exchange) {
            $this->converter = new Converter($this->getRepository(), $exchange);
        } elseif (!isset($this->converter)) {
            throw new \BadMethodCallException('You can only omit parameter $exchange if you already passed it on a previous call to method MoneyWrapper::convert');
        }

        return $this->converter->convert($money, $currency);
    }

    private function getFormatter(): DecimalMoneyFormatter
    {
        return $this->formatter;
    }

    private function getParser(): DecimalMoneyParser
    {
        return $this->parser;
    }

    public function convertAmountSubunits(Money $moneyToConvert, int $subunitChange): Money
    {
        $symbol = $moneyToConvert->getCurrency()->getCode();

        return $this->convert(
            $moneyToConvert,
            new Currency($symbol),
            new FixedExchange([
                $symbol => [ $symbol => 10 ** $subunitChange ],
            ])
        );
    }
}
