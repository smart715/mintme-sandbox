<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitReferralModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class DonationFeeReferralProfitFetcher extends AbstractProfitFetcher
{
    private DonationManagerInterface $donationManager;

    public function __construct(
        DonationManagerInterface $donationManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->donationManager = $donationManager;
    }

    public static function profitModel(): string
    {
        return ProfitReferralModel::class;
    }

    /** @return ProfitReferralModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $rewardsGiven = $this->donationManager->getTotalRewardsGiven($startDate, $endDate);

        $totalLoss = $this->formatMoneyWithNotation((string)-$rewardsGiven['referencer_amount'], Symbols::WEB);
        $donationCount = $rewardsGiven['count'];

        $totalLossInUsd = $this->calculateUsdValue($totalLoss, Symbols::WEB);

        $profits = new ProfitReferralModel(Symbols::WEB, $donationCount, $totalLoss, $totalLossInUsd);

        return $this->appendTotalColumn([$profits]);
    }
}
