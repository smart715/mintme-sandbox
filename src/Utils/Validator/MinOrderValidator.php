<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\TwigExtension\ToMoneyExtension;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Symfony\Contracts\Translation\TranslatorInterface;

class MinOrderValidator implements ValidatorInterface
{
    /** @var TradebleInterface|null */
    private $baseTradable;

    /** @var TradebleInterface|null */
    private $quoteTradable;

    /** @var string */
    private $price;

    /** @var string */
    private $amount;

    /** @var string */
    private $message;

    private string $minimalPriceOrder;

    private MoneyWrapperInterface $moneyWrapper;

    private CryptoRatesFetcherInterface $cryptoRatesFetcher;

    private TranslatorInterface $translator;

    public function __construct(
        ?TradebleInterface $baseTradable,
        ?TradebleInterface $quoteTradable,
        string $price,
        string $amount,
        string $minimalPriceOrder,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator
    ) {
        $this->baseTradable = $baseTradable;
        $this->quoteTradable = $quoteTradable;
        $this->price = $price;
        $this->amount = $amount;
        $this->minimalPriceOrder = $minimalPriceOrder;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->translator = $translator;
    }

    public function validate(): bool
    {
        /** @var Crypto $base */
        $base = $this->baseTradable;

        /** @var Crypto|Token $quote */
        $quote = $this->quoteTradable;

        $baseUnit = $base->getShowSubunit();

        $quoteUnit = $quote instanceof Token
            ? Token::TOKEN_SUBUNIT
            : $quote->getShowSubunit();

        $baseMinimal = $baseUnit > 0
            ? $this->getMinimal($baseUnit)
            : 0;

        $quoteMinimal = $quoteUnit > 0
            ? $this->getMinimal($quoteUnit)
            : 0;

        return $this->price >= $baseMinimal
            && $this->amount >= $quoteMinimal
            && $this->validMinUSD($base);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit + 1, '0');
    }

    private function validMinUSD(TradebleInterface $base): bool
    {
        $baseSymbol = $base->getSymbol();
        $price = $this->moneyWrapper->parse($this->price, Symbols::TOK);
        $amount = $this->moneyWrapper->parse($this->amount, Symbols::TOK);

        $totalOrderAmount = $price->multiply(
            $this->moneyWrapper->format($amount)
        );

        $totalOrderAmountInBase = $this->moneyWrapper->parse(
            $this->moneyWrapper->format($totalOrderAmount),
            $baseSymbol
        );

        $minUsdInBase = $this->getMinUsdInBase($baseSymbol);

        $minUsdInMintme = Symbols::WEB === $baseSymbol ?
            $minUsdInBase : $this->getMinUsdInBase(Symbols::WEB);

        $this->message = $this->translator->trans(
            'place_order.too_small',
            [
                '%valueInUsd%' => $this->minimalPriceOrder,
                '%valueInMintme%' => round(
                    (float)$this->moneyWrapper->format($minUsdInMintme),
                    4
                ),
            ]
        );

        return $totalOrderAmountInBase-> greaterThanOrEqual($minUsdInBase);
    }

    public function getMinUsdInBase(string $baseSymbol): Money
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return  $this->moneyWrapper->convert(
            $this->moneyWrapper->parse((string)$this->minimalPriceOrder, Symbols::USD),
            new Currency($baseSymbol),
            new FixedExchange([
                Symbols::USD => [ $baseSymbol => 1 / $rates[$baseSymbol][Symbols::USD] ],
            ]),
        );
    }
}
