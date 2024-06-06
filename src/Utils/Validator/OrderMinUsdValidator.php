<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\Exception\FetchException;
use App\Entity\TradableInterface;
use App\Exchange\Order;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

class OrderMinUsdValidator implements ValidatorInterface
{
    private TradableInterface $tradable;
    private Money $price;
    private string $message;
    private string $minUsd;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private TranslatorInterface $translator;
    private RebrandingConverterInterface $rebranding;
    private Money $amount;
    private bool $isBuyOrder;

    /** @var Order[] */
    private array $possibleMatchingSellOrders;

    public function __construct(
        TradableInterface $tradable,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        RebrandingConverterInterface $rebranding,
        string $minUsd,
        Money $price,
        Money $amount,
        bool $isBuyOrder,
        array $possibleMatchingSellOrders = []
    ) {
        $this->tradable = $tradable;
        $this->price = $price;
        $this->minUsd = $minUsd;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->translator = $translator;
        $this->rebranding = $rebranding;
        $this->amount = $amount;
        $this->isBuyOrder = $isBuyOrder;
        $this->possibleMatchingSellOrders = $possibleMatchingSellOrders;
    }

    /**
     * @throws FetchException
     */
    public function validate(): bool
    {
        $unit = $this->tradable->getShowSubunit();
        $rates = $this->cryptoRatesFetcher->fetch();
        $symbol = $this->tradable->getSymbol();
        $cryptoUsdRate = $rates[$symbol][Symbols::USD];

        $minUsdInCrypto = (string) BigDecimal::of($this->getMinUsdInCrypto($symbol, $cryptoUsdRate))
            ->multipliedBy('1')
            ->toScale($unit, RoundingMode::HALF_UP);

        $matchingSellOrders = $this->isBuyOrder
            ? array_filter($this->possibleMatchingSellOrders, fn ($o) => $o->getPrice()->lessThanOrEqual($this->price))
            : [];

        $totalPricePaid = $this->calculateTotalPricePaidForTargetAmount($matchingSellOrders, $unit);

        $this->message = $this->translator->trans(
            'place_order.too_small',
            [
                '%valueInUsd%' => $this->minUsd,
                '%valueInBase%' => number_format(
                    (float)$minUsdInCrypto,
                    $unit
                ),
                '%baseSymbol%' => $this->rebranding->convert($this->tradable->getSymbol()),
            ]
        );

        return $totalPricePaid->isGreaterThanOrEqualTo($minUsdInCrypto);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinUsdInCrypto(string $symbol, float $usdRate): string
    {
        return $this->moneyWrapper->format(
            $this->moneyWrapper->convert(
                $this->moneyWrapper->parse($this->minUsd, Symbols::USD),
                new Currency($symbol),
                new FixedExchange([
                    Symbols::USD => [ $symbol => 1 / $usdRate ],
                ]),
            )
        );
    }

    private function calculateTotalPricePaidForTargetAmount(?array $possibleOrders, int $unit): BigDecimal
    {
        if (empty($possibleOrders)) {
            return BigDecimal::of(
                $this->moneyWrapper->format($this->amount)
            )->multipliedBy($this->moneyWrapper->format($this->price));
        }

        $totalPricePaid = BigDecimal::zero();

        $remainingCryptoAmount = BigDecimal::of($this->moneyWrapper->format($this->amount));

        foreach ($possibleOrders as $order) {
            $usedCryptoAmount = min(
                $this->moneyWrapper->format($order->getAmount()),
                $remainingCryptoAmount->toFloat()
            );

            $totalPriceForOrder = BigDecimal::of($this->moneyWrapper->format($order->getPrice()))
                ->multipliedBy($usedCryptoAmount)
                ->toScale($unit, RoundingMode::HALF_UP);

            $remainingCryptoAmount = $remainingCryptoAmount->minus($usedCryptoAmount);

            $totalPricePaid = $totalPricePaid->plus($totalPriceForOrder);

            if ($remainingCryptoAmount->isZero()) {
                break;
            }
        }

        if ($remainingCryptoAmount->isPositive()) {
            $totalPricePaid = $totalPricePaid->plus(
                $remainingCryptoAmount->multipliedBy($this->moneyWrapper->format($this->price))
            );
        }

        return $totalPricePaid;
    }
}
