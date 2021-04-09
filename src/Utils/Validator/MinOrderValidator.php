<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Money\Currency;
use Money\Exchange\FixedExchange;

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
    private $message = 'Amount is low';

    private string $minimalPriceOrder;

    private MoneyWrapperInterface $moneyWrapper;

    private CryptoRatesFetcherInterface $cryptoRatesFetcher;

    public function __construct(
        ?TradebleInterface $baseTradable,
        ?TradebleInterface $quoteTradable,
        string $price,
        string $amount,
        string $minimalPriceOrder,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher
    ) {
        $this->baseTradable = $baseTradable;
        $this->quoteTradable = $quoteTradable;
        $this->price = $price;
        $this->amount = $amount;
        $this->minimalPriceOrder = $minimalPriceOrder;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
    }

    public function validate(): bool
    {
        /** @var Crypto $base */
        $base = $this->baseTradable;

        /** @var Crypto|Token $quote */
        $quote = $this->quoteTradable;

        $baseUnit = $base
            ? $base->getShowSubunit()
            : 0;

        $quoteUnit = 0;

        if ($quote) {
            $quoteUnit = $quote instanceof Token
                ? Token::TOKEN_SUBUNIT
                : $quote->getShowSubunit();
        }

        $scale = $baseUnit > $quoteUnit
            ? $baseUnit
            : $quoteUnit;

        $baseMinimal = $baseUnit > 0
            ? $this->getMinimal($baseUnit)
            : 0;

        $quoteMinimal = $quoteUnit > 0
            ? $this->getMinimal($quoteUnit)
            : 0;

        return $this->price >= $baseMinimal
            && $this->amount >= $quoteMinimal
            && $this->checkOrderPriceMinimal($base, $scale);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit + 1, '0');
    }

    private function checkOrderPriceMinimal(TradebleInterface $base, int $scale): bool
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        $totalOrderAmount = BigDecimal::of((float)$this->price)
           ->toScale($scale)
           ->multipliedBy(
               BigDecimal::of((float)$this->amount)->toScale($scale)
           )->toFloat();

        $totalOrderAmountInBase = $this->moneyWrapper->parse(
            (string)$totalOrderAmount,
            $base->getSymbol()
        );

        $totalOrderAmountInUsd = $this->moneyWrapper->parse(
            $totalOrderAmountInBase->multiply($rates[$base->getSymbol()][Symbols::USD])->getAmount(),
            Symbols::USD
        );

        // TODO compare to value from paramerters and return the properly message
    }
}
