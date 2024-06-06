<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitBotModel;
use App\Manager\Model\Profit\ProfitInternalTransactionModel;
use App\Manager\Model\Profit\ProfitReferralModel;
use App\Manager\Model\Profit\ProfitServiceModel;
use App\Manager\Model\Profit\ProfitTradingModel;
use App\Manager\Model\Profit\ProfitTransactionModel;
use DateTimeImmutable;

interface ProfitManagerInterface
{
    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function getDeploymentProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function getAdditionalMarketsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function getCommentTipFeesProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitTransactionModel[]|AbstractProfitModel[] */
    public function getTransactionsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitTradingModel[]|AbstractProfitModel[] */
    public function getTradingProfits(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitReferralModel[]|AbstractProfitModel[] */
    public function getTokenReferralProfits(
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitReferralModel[]|AbstractProfitModel[] */
    public function getDonationFeeReferralProfits(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return ProfitInternalTransactionModel[]|AbstractProfitModel[] */
    public function getInternalTransactionsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /** @return array<string, ProfitBotModel[]|AbstractProfitModel[]> */
    public function getBotsProfits(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /** @return AbstractProfitModel[] */
    public function getProfitsSummary(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true,
        bool $withTrackedAccounts = true
    ): array;
}
