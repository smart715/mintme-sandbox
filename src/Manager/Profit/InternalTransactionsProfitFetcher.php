<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\InternalTransactionManager;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitInternalTransactionModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class InternalTransactionsProfitFetcher extends AbstractProfitFetcher
{
    private InternalTransactionManager $internalTransactionManager;

    public function __construct(
        InternalTransactionManager $tokenCryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->internalTransactionManager = $tokenCryptoManager;
    }

    public static function profitModel(): string
    {
        return ProfitInternalTransactionModel::class;
    }

    /** @return ProfitInternalTransactionModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $internalTransactions = $this->internalTransactionManager->getInternalTransactionsProfits($startDate, $endDate);

        if (!$withMintMe) {
            $internalTransactions = array_filter($internalTransactions, fn(array $t) => !$this->isWEB($t['network']));
        }

        $internalTransactionsProfit = array_map(function (array $internalTransaction) {
            $crypto = $internalTransaction['network'];
            $count = $internalTransaction['withdrawalCount'];
            $withdrawalsFee = $internalTransaction['withdrawalsFee'];

            $profit = $this->formatMoneyWithNotation((string)$withdrawalsFee, $crypto);
            $profitInUsd = $this->calculateUsdValue($profit, $crypto);

            return new ProfitInternalTransactionModel(
                $crypto,
                $count,
                $profit,
                $profitInUsd
            );
        }, $internalTransactions);

        return $this->appendTotalColumn($internalTransactionsProfit);
    }
}
