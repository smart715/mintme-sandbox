<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Config\TokenPromotionConfig;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

class TokenPromotionCostFetcher implements TokenPromotionCostFetcherInterface
{
    private TokenPromotionConfig $config;
    private CachedCryptoRatesFetcher $cachedCryptoRatesFetcher;
    private MoneyWrapperInterface $moneyWrapper;

    /** @var Crypto[] */
    private array $cryptosMap;

    public function __construct(
        TokenPromotionConfig $config,
        CachedCryptoRatesFetcher $cachedCryptoRatesFetcher,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->config = $config;
        $this->cachedCryptoRatesFetcher = $cachedCryptoRatesFetcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptosMap = $cryptoManager->findAllIndexed('symbol');
    }

    /**
     * @throws \InvalidArgumentException Wrong tariff duration provided
     * @throws FetchException
     */
    public function getCost(string $tariffDuration): array
    {
        $prices = $this->cachedCryptoRatesFetcher->fetch();
        $rates = [];

        $tariff = $this->config->getTariff($tariffDuration);

        if (!$tariff) {
            throw new \InvalidArgumentException('Invalid tariff duration');
        }

        foreach (array_keys($prices) as $symbol) {
            $rates[$symbol] = $this->rate(
                (string)$tariff['cost'],
                $symbol,
                $prices
            );
        }

        return $rates;
    }

    public function getCosts(): array
    {
        $prices = $this->cachedCryptoRatesFetcher->fetch();
        $rates = [];

        foreach ($this->config->getTariffs() as $tariff) {
            foreach (array_keys($prices) as $symbol) {
                $rates[$tariff['duration']][$symbol] = $this->rate(
                    (string)$tariff['cost'],
                    $symbol,
                    $prices
                );
            }
        }

        return $rates;
    }

    private function rate(string $cost, string $symbol, array $prices): Money
    {
        $crypto = $this->cryptosMap[$symbol];

        $converted = $this->moneyWrapper->convert(
            $this->moneyWrapper->parse($cost, Symbols::USD),
            new Currency($symbol),
            new FixedExchange([
                Symbols::USD => [ $symbol => 1 / $prices[$symbol][Symbols::USD]],
            ])
        );

        $roundedToSubunit = (string)BigDecimal::of($this->moneyWrapper->format($converted))
            ->multipliedBy('1')
            ->toScale($crypto->getShowSubunit(), RoundingMode::HALF_UP);

        return $this->moneyWrapper->parse($roundedToSubunit, $crypto->getSymbol());
    }
}
