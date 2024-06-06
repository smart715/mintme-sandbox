<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitServiceModel;
use App\Manager\TokenCryptoManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class AdditionalMarketsProfitFetcher extends AbstractProfitFetcher
{
    private TokenCryptoManagerInterface $tokenCryptoManager;

    public function __construct(
        TokenCryptoManagerInterface $tokenCryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->tokenCryptoManager = $tokenCryptoManager;
    }

    public static function profitModel(): string
    {
        return ProfitServiceModel::class;
    }

    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $totalProfits = $this->tokenCryptoManager->getTotalCostPerCrypto($startDate, $endDate);

        if (!$withMintMe) {
            $totalProfits = array_filter($totalProfits, fn(array $cryptoData) => !$this->isWEB($cryptoData['symbol']));
        }

        $additionalMarketsProfit = array_map(function (array $profitPerCrypto) {
            $crypto = $profitPerCrypto['symbol'];
            $count = $profitPerCrypto['count'];
            $totalCost = $profitPerCrypto['totalCost'];

            $totalCost = $this->formatMoneyWithNotation($totalCost, $crypto);
            $totalCostInUsd = $this->calculateUsdValue($totalCost, $crypto);

            return new ProfitServiceModel($crypto, $count, $totalCost, $totalCostInUsd);
        }, $totalProfits);

        return $this->appendTotalColumn($additionalMarketsProfit);
    }
}
