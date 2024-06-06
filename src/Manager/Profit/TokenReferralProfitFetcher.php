<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitReferralModel;
use App\Repository\DeployTokenRewardRepository;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class TokenReferralProfitFetcher extends AbstractProfitFetcher
{
    private DeployTokenRewardRepository $deployTokenRewardRepository;

    public function __construct(
        DeployTokenRewardRepository $deployTokenRewardRepository,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->deployTokenRewardRepository = $deployTokenRewardRepository;
    }

    public static function profitModel(): string
    {
        return ProfitReferralModel::class;
    }

    /** @return ProfitReferralModel[]|AbstractProfitModel[] */
    public function fetch(?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $referrals = $this->deployTokenRewardRepository->getReferralProfits($startDate, $endDate);

        if (!$withMintMe) {
            $referrals = array_filter($referrals, fn(array $referral) => !$this->isWEB($referral['currency']));
        }

        $referralProfits = array_map(function (array $referralProfit) {
            $crypto = $referralProfit['currency'];
            $totalProfit = -$referralProfit['total_reward'];
            $rewardCount = $referralProfit['reward_count'];

            $totalProfit = $this->formatMoneyWithNotation((string)$totalProfit, $crypto);
            $totalRewardInUsd = $this->calculateUsdValue($totalProfit, $crypto);

            return new ProfitReferralModel($crypto, $rewardCount, $totalProfit, $totalRewardInUsd);
        }, $referrals);

        return $this->appendTotalColumn($referralProfits);
    }
}
