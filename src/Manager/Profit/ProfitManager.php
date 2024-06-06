<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\TotalProfitModel;
use App\Services\TranslatorService\TranslatorInterface;
use DateTimeImmutable;

/** @codeCoverageIgnore */
class ProfitManager implements ProfitManagerInterface
{
    private TranslatorInterface $translator;
    private DeploymentProfitFetcher $deploymentProfitFetcher;
    private AdditionalMarketsProfitFetcher $additionalMarketsProfitFetcher;
    private CommentTipFeesFetcher $commentTipFeesFetcher;
    private InternalTransactionsProfitFetcher $internalTransactionsProfitFetcher;
    private BlockchainTransactionProfitFetcher $blockchainTransactionProfitFetcher;
    private TokenReferralProfitFetcher $tokenReferralProfitFetcher;
    private DonationFeeReferralProfitFetcher $donationFeeReferralProfitFetcher;
    private TradingProfitsFetcher $tradingProfitsFetcher;
    private BotsProfitsFetcher $botsProfitsFetcher;

    public function __construct(
        TranslatorInterface $translator,
        DeploymentProfitFetcher $deploymentProfitManager,
        AdditionalMarketsProfitFetcher $additionalMarketsProfitFetcher,
        CommentTipFeesFetcher $commentTipFeesFetcher,
        InternalTransactionsProfitFetcher $internalTransactionsProfitFetcher,
        BlockchainTransactionProfitFetcher $blockchainTransactionProfitFetcher,
        TokenReferralProfitFetcher $tokenReferralProfitFetcher,
        DonationFeeReferralProfitFetcher $donationFeeReferralProfitFetcher,
        TradingProfitsFetcher $tradingProfitsFetcher,
        BotsProfitsFetcher $botsProfitsFetcher
    ) {
        $this->translator = $translator;
        $this->deploymentProfitFetcher = $deploymentProfitManager;
        $this->additionalMarketsProfitFetcher = $additionalMarketsProfitFetcher;
        $this->commentTipFeesFetcher = $commentTipFeesFetcher;
        $this->internalTransactionsProfitFetcher = $internalTransactionsProfitFetcher;
        $this->blockchainTransactionProfitFetcher = $blockchainTransactionProfitFetcher;
        $this->tokenReferralProfitFetcher = $tokenReferralProfitFetcher;
        $this->donationFeeReferralProfitFetcher = $donationFeeReferralProfitFetcher;
        $this->tradingProfitsFetcher = $tradingProfitsFetcher;
        $this->botsProfitsFetcher = $botsProfitsFetcher;
    }

    /** @inheritDoc */
    public function getDeploymentProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->deploymentProfitFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getAdditionalMarketsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->additionalMarketsProfitFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getCommentTipFeesProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->commentTipFeesFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getTransactionsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->blockchainTransactionProfitFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getInternalTransactionsProfit(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->internalTransactionsProfitFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getTokenReferralProfits(
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->tokenReferralProfitFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getDonationFeeReferralProfits(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        if (!$withMintMe) {
            return [];
        }

        return $this->donationFeeReferralProfitFetcher->fetch($startDate, $endDate);
    }

    /** @inheritDoc */
    public function getTradingProfits(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        return $this->tradingProfitsFetcher->fetch($startDate, $endDate, $withMintMe);
    }

    /** @inheritDoc */
    public function getBotsProfits(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->botsProfitsFetcher->fetchAll($startDate, $endDate);
    }

    /** @inheritDoc */
    public function getProfitsSummary(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true,
        bool $withTrackedAccounts = true
    ): array {
        $titles = [
            $this->translator->trans('mintme.api.profit.deployment_table'),
            $this->translator->trans('mintme.api.profit.additional_markets_table'),
            $this->translator->trans('mintme.api.profit.comment_tips_fees_table'),
            $this->translator->trans('mintme.api.profit.transactions_table'),
            $this->translator->trans('mintme.api.profit.internal_transactions_table'),
            $this->translator->trans('mintme.api.profit.token_referral_table'),
            $this->translator->trans('mintme.api.profit.donation_referral_table'),
            $this->translator->trans('mintme.api.profit.trading_table'),
        ];

        if ($withTrackedAccounts) {
            $titles[] = $this->translator->trans('mintme.api.profit.bots_table');
        }

        $titles[] = $this->translator->trans('total');

        $profits = [
            $this->deploymentProfitFetcher,
            $this->additionalMarketsProfitFetcher,
            $this->commentTipFeesFetcher,
            $this->blockchainTransactionProfitFetcher,
            $this->internalTransactionsProfitFetcher,
            $this->tokenReferralProfitFetcher,
            $this->donationFeeReferralProfitFetcher,
            $this->tradingProfitsFetcher,
        ];

        if ($withTrackedAccounts) {
            $profits[] = $this->botsProfitsFetcher;
        }

        return $this->arrayCombine($titles, $this->appendTotalColumn($profits, $startDate, $endDate, $withMintMe));
    }

    private function arrayCombine(array $keys, array $values): array
    {
        $result = [];

        foreach ($keys as $i => $k) {
            $result[$k] = $values[$i];
        }

        return $result;
    }

    /**
     * @param AbstractProfitFetcher[] $profitFetchers
     * @return TotalProfitModel[]|AbstractProfitModel[]
     */
    private function appendTotalColumn(
        array $profitFetchers,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe
    ): array {
        $profits = [];

        foreach ($profitFetchers as $profitFetcher) {
            try {
                $profits[] = $profitFetcher->total($startDate, $endDate, $withMintMe);
            } catch (\Throwable $e) {
                $profits[] = $this->failedRecord();
            }
        }

        $summaryColumnByUsd = array_reduce(
            $profits,
            function (AbstractProfitModel $summary, AbstractProfitModel $profit): AbstractProfitModel {
                return $this->accumulateSummary($summary, $profit);
            },
            new TotalProfitModel($this->translator->trans('total'))
        );

        return [...$profits, $summaryColumnByUsd];
    }

    private function accumulateSummary(AbstractProfitModel $summary, AbstractProfitModel $profit): AbstractProfitModel
    {
        return $summary
            ->setCount(bcadd($summary->getCount(), $profit->getCount()))
            ->setProfitInUsd(bcadd($summary->getProfitInUsd(), $profit->getProfitInUsd(), 2));
    }

    private function failedRecord(): TotalProfitModel
    {
        return new TotalProfitModel("ERROR", "0", "0");
    }
}
