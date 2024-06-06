<?php declare(strict_types = 1);

namespace App\Manager\Finance;

use App\Communications\GeckoCoin\Config\GeckoCoinConfig;
use App\Communications\GeckoCoin\GeckoCoinCommunicatorInterface;
use App\Communications\GeckoCoin\Model\SimplePrice;
use App\Entity\Crypto;
use App\Entity\Finance\FinanceBalance;
use App\Manager\Model\FinanceBalanceModel;
use App\Manager\Model\FinanceIncomeModel;
use App\Manager\Model\FinanceIncomeViewModel;
use App\Repository\Finance\FinanceBalanceRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;
use Money\Money;

class FinanceBalanceManager implements FinanceBalanceManagerInterface
{
    private FinanceBalanceRepository $repository;
    private GeckoCoinConfig $geckoCoinConfig;
    private GeckoCoinCommunicatorInterface $geckoCoinCommunicator;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        FinanceBalanceRepository $repository,
        GeckoCoinConfig $geckoCoinConfig,
        GeckoCoinCommunicatorInterface $geckoCoinCommunicator,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->geckoCoinCommunicator = $geckoCoinCommunicator;
        $this->geckoCoinConfig = $geckoCoinConfig;
        $this->repository = $repository;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getBalance(?Crypto $crypto = null): array
    {
        $result = [];
        $balances = $this->repository->findLatest($crypto);

        /** @var FinanceBalance $balance */
        foreach ($balances as $balance) {
            $crypto = $balance->getCrypto();

            $blockchainBalance = $this->moneyWrapper->parse((string)$balance->getBlockchainBalance(), $crypto);
            $usersBalance = $this->moneyWrapper->parse((string)$balance->getUsersBalance(), $crypto);
            $coldWalletBalance = $this->moneyWrapper->parse((string)$balance->getColdWalletBalance(), $crypto);
            $withdrawFee = $this->moneyWrapper->parse((string)$balance->getWithdrawFeeToPay(), $crypto);
            $difference = $blockchainBalance->subtract($usersBalance);

            $financeBalanceModel = new FinanceBalanceModel(
                $balance->getTimestamp()->format('Y-m-d H:m:s'),
                $balance->getCrypto(),
                $this->moneyWrapper->format($blockchainBalance),
                $this->moneyWrapper->format($usersBalance),
                $this->moneyWrapper->format($difference),
                $this->moneyWrapper->format($withdrawFee),
                $this->moneyWrapper->format(
                    $difference->add($withdrawFee)->add($coldWalletBalance)
                ),
                $this->moneyWrapper->format($coldWalletBalance)
            );

            $result[] = $financeBalanceModel;
        }

        return $result;
    }

    public function getIncome(DateTimeImmutable $from, DateTimeImmutable $to): FinanceIncomeViewModel
    {
        $cryptosSymbol = array_keys($this->geckoCoinConfig->getCryptos());

        $incomes = [];
        $totalUsd = $this->moneyWrapper->parse('0', Symbols::USD);

        foreach ($cryptosSymbol as $cryptoSymbol) {
            $cryptoIncome = $this->getCryptoIncome($cryptoSymbol, $from, $to);

            if (!$cryptoIncome) {
                continue;
            }

            $incomes[] = $cryptoIncome;
            $totalUsd = $totalUsd->add($this->moneyWrapper->parse($cryptoIncome->getUsdValue(), Symbols::USD));
        }

        $viewModel = new FinanceIncomeViewModel();
        $viewModel
            ->setIncomes($incomes)
            ->setTotalUsd(
                $this->moneyWrapper->format($totalUsd)
            );

        return $viewModel;
    }

    private function getCryptoIncome(
        string $cryptoSymbol,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): ?FinanceIncomeModel {
        $balances = $this->repository->findBalancesByRange($cryptoSymbol, $from, $to);

        if (!$balances) {
            return null;
        }

        $firstBalance = $balances[array_key_first($balances)];
        $lastBalance = $balances[array_key_last($balances)];

        $startDate = $firstBalance->getTimestamp()->format('Y-m-d H:i:s');
        $startAmount = $this->moneyWrapper->parse((string)$firstBalance->getBlockchainBalance(), $cryptoSymbol)
            ->subtract($this->moneyWrapper->parse((string)$firstBalance->getUsersBalance(), $cryptoSymbol));

        $endDate = $lastBalance->getTimeStamp()->format('Y-m-d H:i:s');
        $endAmount = $this->moneyWrapper->parse((string)$lastBalance->getBlockchainBalance(), $cryptoSymbol)
            ->subtract($this->moneyWrapper->parse((string)$lastBalance->getUsersBalance(), $cryptoSymbol));

        $startAmountStr = $this->moneyWrapper->format($startAmount);
        $endAmountStr = $this->moneyWrapper->format($endAmount);

        return new FinanceIncomeModel(
            $cryptoSymbol,
            $startDate,
            $endDate,
            $startAmountStr,
            $endAmountStr,
            $this->moneyWrapper->format($endAmount->subtract($startAmount)),
            $this->moneyWrapper->format($this->calculateUsdValue($cryptoSymbol, $endAmountStr, $startAmountStr))
        );
    }

    private function calculateUsdValue(string $cryptoSymbol, string $endAmount, string $startAmount): Money
    {
        $endAmount = $this->moneyWrapper->parse($endAmount, Symbols::USD);
        $startAmount = $this->moneyWrapper->parse($startAmount, Symbols::USD);

        $cryptos = $this->geckoCoinConfig->getCryptos();
        $cryptoName = $cryptos[$cryptoSymbol];
        $simplePriceData = new SimplePrice([$cryptoName], ['usd']);
        $price = $this->geckoCoinCommunicator->getSimplePrice($simplePriceData)[$cryptoName]['usd'];

        return $endAmount->subtract($startAmount)->multiply((string)$price);
    }
}
