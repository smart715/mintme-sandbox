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

    public function validate(): Bool
    {
        /** @var Crypto $base */
        $base = $this->baseTradable;

        /** @var Crypto|Token $quote */
        $quote = $this->quoteTradable;

        $baseUnit = $base->getShowSubunit();

        $quoteUnit = $quote instanceof Token
            ? Token::TOKEN_SUBUNIT
            : $quote->getShowSubunit();


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
            && $this->checkOrderPriceMinimal($scale);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit + 1, '0');
    }

    private function checkOrderPriceMinimal(int $scale): bool
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        $totalOrderAmount = BigDecimal::of((float)$this->price)
           ->toScale($scale)
           ->multipliedBy(
               BigDecimal::of((float)$this->amount)->toScale($scale)
           )->toFloat();

        $totalOrderAmountInBase = $this->moneyWrapper->parse(
            (string)$totalOrderAmount,
            Symbols::WEB
        );

        $minimalPriceOrderInBase =  $this->moneyWrapper->convert(
            $this->moneyWrapper->parse((string)$this->minimalPriceOrder, Symbols::USD),
            new Currency(Symbols::WEB),
            new FixedExchange([
                Symbols::USD => [ Symbols::WEB => 1 / $rates[Symbols::WEB][Symbols::USD] ],
            ]),
        );

        $toMoney = new ToMoneyExtension();

        $this->message = $this->translator->trans(
            'place_order.too_small',
            [
                '%valueInUsd%' => $this->minimalPriceOrder,
                '%valueInMintme%' => $toMoney->toMoney(
                    $this->moneyWrapper->format($minimalPriceOrderInBase)
                ),
            ]
        );

        return $totalOrderAmountInBase->greaterThanOrEqual($minimalPriceOrderInBase);
    }
}
