<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\Exception\FetchException;
use App\Entity\TradableInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

class MinUsdValidator implements ValidatorInterface
{
    private TradableInterface $tradable;

    private string $amount;

    private string $message;

    private string $minUsd;

    private MoneyWrapperInterface $moneyWrapper;

    private CryptoRatesFetcherInterface $cryptoRatesFetcher;

    private TranslatorInterface $translator;

    private RebrandingConverterInterface $rebranding;

    private array $fallbackMinCryptoAmount;

    public function __construct(
        TradableInterface $tradable,
        string $amount,
        string $minUsd,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        RebrandingConverterInterface $rebranding,
        array $fallbackMinCryptoAmount
    ) {
        $this->tradable = $tradable;
        $this->amount = $amount;
        $this->minUsd = $minUsd;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->translator = $translator;
        $this->rebranding = $rebranding;
        $this->fallbackMinCryptoAmount = $fallbackMinCryptoAmount;
    }

    public function validate(): bool
    {
        $amount = $this->moneyWrapper->parse(
            $this->amount,
            $this->tradable->getMoneySymbol()
        );

        $unit = $this->tradable->getShowSubunit();

        try {
            $rates = $this->getMinUsdInCrypto($this->tradable->getSymbol());
            $minUsdInCrypto = (string) BigDecimal::of($rates)
                ->multipliedBy('1')
                ->toScale($unit, RoundingMode::HALF_UP);
        } catch (FetchException $e) {
            if (!array_key_exists($this->tradable->getSymbol(), $this->fallbackMinCryptoAmount)) {
                throw new FetchException('No fallback value for ' . $this->tradable->getSymbol());
            }

            $minUsdInCrypto = (string)$this->fallbackMinCryptoAmount[$this->tradable->getSymbol()];
        }

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

        return $amount->greaterThanOrEqual($this->moneyWrapper->parse($minUsdInCrypto, $this->tradable->getSymbol()));
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @throws FetchException
     */
    private function getMinUsdInCrypto(string $symbol): string
    {
        $rates = $this->cryptoRatesFetcher->fetch();

        return $this->moneyWrapper->format(
            $this->moneyWrapper->convert(
                $this->moneyWrapper->parse($this->minUsd, Symbols::USD),
                new Currency($symbol),
                new FixedExchange([
                    Symbols::USD => [ $symbol => 1 / $rates[$symbol][Symbols::USD] ],
                ]),
            )
        );
    }
}
