<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitTransactionModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;

class BlockchainTransactionProfitFetcher extends AbstractProfitFetcher
{
    private WithdrawGatewayInterface $cryptoWithdrawGateway;

    public function __construct(
        WithdrawGatewayInterface $cryptoWithdrawGateway,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->cryptoWithdrawGateway = $cryptoWithdrawGateway;
    }

    public static function profitModel(): string
    {
        return ProfitTransactionModel::class;
    }

    /** @return ProfitTransactionModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $cryptos = $withMintMe
            ? $this->cryptos
            : array_filter($this->cryptos, fn(string $crypto) => !$this->isWEB($crypto));

        $transactionsProfit = array_map(function (string $crypto) use ($startDate, $endDate) {
            $result = $this->cryptoWithdrawGateway->getCryptoIncome($crypto, $startDate, $endDate);

            $deposits = $this->moneyWrapper->parse((string)$result['deposits'], $crypto);
            $withdrawals = $this->moneyWrapper->parse((string)$result['withdrawals'], $crypto);
            $depositsFee = $this->moneyWrapper->parse((string)$result['depositsFee'], $crypto);
            $withdrawalsFee = $this->moneyWrapper->parse((string)$result['withdrawalsFee'], $crypto);

            $totalDeposit = $this->formatMoneyWithNotation($deposits->getAmount(), $crypto);
            $totalWithdrawal = $this->formatMoneyWithNotation($withdrawals->getAmount(), $crypto);
            $totalDepositFee = $this->formatMoneyWithNotation($depositsFee->getAmount(), $crypto);
            $totalWithdrawalFee = $this->formatMoneyWithNotation($withdrawalsFee->getAmount(), $crypto);
            $profit = $this->moneyWrapper->format(
                $this->moneyWrapper->parse(bcsub($totalWithdrawalFee, $totalDepositFee, 8), $crypto)
            );
            $profitInUsd = $this->calculateUsdValue($profit, $crypto);

            return new ProfitTransactionModel(
                $crypto,
                $totalDeposit,
                $totalWithdrawal,
                $totalDepositFee,
                $totalWithdrawalFee,
                $profit,
                $profitInUsd
            );
        }, $cryptos);

        return $this->appendTotalColumn($transactionsProfit);
    }
}
