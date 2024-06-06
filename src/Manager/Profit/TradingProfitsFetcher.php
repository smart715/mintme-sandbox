<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Exchange\Market\MarketFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitTradingModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class TradingProfitsFetcher extends AbstractProfitFetcher
{
    private MarketFetcherInterface $marketFetcher;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->marketFetcher = $marketFetcher;
    }

    public static function profitModel(): string
    {
        return ProfitTradingModel::class;
    }

    /** @return ProfitTradingModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $marketsAnalytics = $this->marketFetcher->getMarketAnalytics(
            $startDate->getTimestamp(),
            $endDate->getTimestamp()
        );
        $markets = array_keys($marketsAnalytics);
        $markets = array_filter($markets, function (string $market) {
            [$base, $quote] = $this->separateMarketSymbols($market);

            return $base && $quote;
        });

        $marketsAnalyticsProfit = array_map(function (string $market) use ($withMintMe, $marketsAnalytics) {
            $marketAnalytics = $marketsAnalytics[$market];

            [$base, $quote] = $this->separateMarketSymbols($market);
            $isTokenMarket = Symbols::TOK === $base; // trader pays fee in crypto in token markets

            $count = (string)$marketAnalytics['count'];

            $totalFee = $marketAnalytics['total_fee'];

            if (($isTokenMarket && $this->isWEB($base) && !$withMintMe) || ($this->isWEB($base) && !$withMintMe)) {
                $totalFeeInUsd = '0';
            } else {
                $totalFeeInUsd = $this->calculateUsdValue($totalFee, $isTokenMarket ? $quote : $base);
            }

            $totalDealFee = $marketAnalytics['total_deal_fee'];

            $totalDealFeeInUsd = $this->isWEB($quote) && !$withMintMe
                ? '0'
                : $this->calculateUsdValue($totalDealFee, $quote);

            $totalInUsd = $this->moneyWrapper->format(
                $this->moneyWrapper->parse($totalFeeInUsd, Symbols::USD)->add(
                    $this->moneyWrapper->parse($totalDealFeeInUsd, Symbols::USD)
                )
            );

            return new ProfitTradingModel(
                $base . '/' . $quote,
                $base,
                $quote,
                $totalFee,
                $totalFeeInUsd,
                $totalDealFee,
                $totalDealFeeInUsd,
                $totalInUsd,
                $count
            );
        }, $markets);

        return $this->appendTotalColumn($marketsAnalyticsProfit);
    }

    private function separateMarketSymbols(string $market): array
    {
        preg_match('/(' . implode('|', $this->cryptos) . '|' . Symbols::TOK . ')(.*)/', $market, $matches);
        [, $base, $quote] = $matches;

        return [$base, $quote];
    }
}
