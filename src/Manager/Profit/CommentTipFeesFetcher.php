<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CommentTipsManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitServiceModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class CommentTipFeesFetcher extends AbstractProfitFetcher
{
    private CommentTipsManagerInterface $commentTipsManager;

    public function __construct(
        CommentTipsManagerInterface $commentTipsManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->commentTipsManager = $commentTipsManager;
    }

    public static function profitModel(): string
    {
        return ProfitServiceModel::class;
    }

    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $totalProfits = $this->commentTipsManager->getTotalFeesPerCrypto($startDate, $endDate);

        if (!$withMintMe) {
            $totalProfits = array_filter($totalProfits, fn(array $cryptoData) => !$this->isWEB($cryptoData['symbol']));
        }

        $commentTipFeesProfit = array_map(function (array $commentsFee) {
            $crypto = $commentsFee['symbol'];
            $count = $commentsFee['count'];
            $total = $commentsFee['total'];

            $totalCost = $this->formatMoneyWithNotation($total, $crypto);
            $totalCostInUsd = $this->calculateUsdValue($totalCost, $crypto);

            return new ProfitServiceModel($crypto, $count, $totalCost, $totalCostInUsd);
        }, $totalProfits);

        return $this->appendTotalColumn($commentTipFeesProfit);
    }
}
