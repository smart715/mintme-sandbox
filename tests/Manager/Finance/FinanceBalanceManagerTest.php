<?php declare(strict_types = 1);

namespace App\Tests\Manager\Finance;

use App\Communications\GeckoCoin\Config\GeckoCoinConfig;
use App\Communications\GeckoCoin\GeckoCoinCommunicator;
use App\Entity\Finance\FinanceBalance;
use App\Manager\Finance\FinanceBalanceManager;
use App\Manager\Model\FinanceBalanceModel;
use App\Manager\Model\FinanceIncomeModel;
use App\Manager\Model\FinanceIncomeViewModel;
use App\Repository\Finance\FinanceBalanceRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class FinanceBalanceManagerTest extends TestCase
{
    /** @dataProvider getBalances */
    public function testGetBalance(FinanceBalance $balanceModel): void
    {
        $repo = $this->createMock(FinanceBalanceRepository::class);
        $geckoConfig = $this->createGeckoConfig();
        $geckoComm = $this->createMock(GeckoCoinCommunicator::class);
        $mw = $this->createMoneyWrapper();

        $repo
            ->expects($this->once())
            ->method('findLatest')
            ->willReturn([$balanceModel]);

        $balanceManager = new FinanceBalanceManager(
            $repo,
            $geckoConfig,
            $geckoComm,
            $mw
        );

        $financeModel = new FinanceBalanceModel(
            $balanceModel->getTimestamp()->format('Y-m-d H:m:s'),
            $balanceModel->getCrypto(),
            (string)$balanceModel->getBlockchainBalance(),
            (string)$balanceModel->getUsersBalance(),
            (string)($balanceModel->getBlockchainBalance() - $balanceModel->getUsersBalance()),
            (string)$balanceModel->getWithdrawFeeToPay(),
            (string)($balanceModel->getBlockchainBalance() - $balanceModel->getUsersBalance() +
            $balanceModel->getWithdrawFeeToPay() + $balanceModel->getColdWalletBalance()),
            (string)$balanceModel->getColdWalletBalance()
        );

        $this->assertEquals(
            $balanceManager->getBalance(),
            [$financeModel]
        );
    }

    public function testGetIncome(): void
    {
        $repo = $this->createMock(FinanceBalanceRepository::class);
        $geckoConfig = $this->createGeckoConfig(2);
        $geckoComm = $this->createMock(GeckoCoinCommunicator::class);
        $geckoComm
            ->expects($this->once())
            ->method('getSimplePrice')
            ->willReturn([
                'webchain' => [
                    'usd' => 1,
                ],
            ]);

        $mw = $this->createMoneyWrapper();
        $dateTime1 = new \DateTimeImmutable();
        $dateTime2 = new \DateTimeImmutable();
        $webIncomes = $this->getWEBIncomes();
        
        $repo
            ->expects($this->once())
            ->method('findBalancesByRange')
            ->with('WEB', $dateTime1, $dateTime2)
            ->willReturn($webIncomes);

        $balanceManager = new FinanceBalanceManager(
            $repo,
            $geckoConfig,
            $geckoComm,
            $mw
        );

        $webIncomeModel = new FinanceIncomeModel(
            $webIncomes[0]->getCrypto(),
            $webIncomes[0]->getTimestamp()->format('Y-m-d H:i:s'),
            $webIncomes[1]->getTimestamp()->format('Y-m-d H:i:s'),
            '0',
            '50',
            '50',
            '50'
        );

        $incomeView = new FinanceIncomeViewModel();
        $incomeView
            ->setIncomes([$webIncomeModel])
            ->setTotalUsd('50');

        $cryptoIncome = $balanceManager->getIncome($dateTime1, $dateTime2);

        $this->assertEquals(
            $cryptoIncome,
            $incomeView
        );
    }

    /**
     * @return FinanceBalance[]
     */
    private function getWEBIncomes(): array
    {
        $balanceWEB1 = new FinanceBalance();
        $balanceWEB2 = new FinanceBalance();

        $balanceWEB1
            ->setTimestamp(new \DateTimeImmutable('2020-01-01 12:00:00'))
            ->setBlockchainBalance(1)
            ->setCrypto('WEB')
            ->setUsersBalance(1)
            ->setFee(0)
            ->setFeePaid(0)
            ->setWithdrawFeeToPay(2)
            ->setBotBalance(0)
            ->setColdWalletBalance(0);

        $balanceWEB2
            ->setTimestamp(new \DateTimeImmutable('2020-02-01 12:00:00'))
            ->setBlockchainBalance(250)
            ->setCrypto('WEB')
            ->setUsersBalance(200)
            ->setFee(0)
            ->setFeePaid(.0)
            ->setWithdrawFeeToPay(1)
            ->setBotBalance(0)
            ->setColdWalletBalance(0);
        
        return [
            $balanceWEB1,
            $balanceWEB2,
        ];
    }

    public function getBalances(): array
    {
        $balanceBTC = new FinanceBalance();
        $balanceBNB = new FinanceBalance();

        $balanceBTC
            ->setTimestamp(new \DateTimeImmutable())
            ->setBlockchainBalance(123)
            ->setCrypto('BTC')
            ->setUsersBalance(234234)
            ->setFee(0)
            ->setFeePaid(0)
            ->setWithdrawFeeToPay(1161)
            ->setBotBalance(3)
            ->setColdWalletBalance(0);

        $balanceBNB
            ->setTimestamp(new \DateTimeImmutable('200-01-01 12:00:00'))
            ->setBlockchainBalance(234234)
            ->setCrypto('BNB')
            ->setUsersBalance(6351)
            ->setFee(0)
            ->setFeePaid(0)
            ->setWithdrawFeeToPay(444)
            ->setBotBalance(0)
            ->setColdWalletBalance(0);

        return [
            'BTC' => [$balanceBTC],
            'BNB' => [$balanceBNB],
        ];
    }

    private function createMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        $moneyWrapper->method('parse')->willReturnCallback(
            function (string $amount, string $symbol): Money {
                return new Money($amount, new Currency($symbol));
            }
        );

        $moneyWrapper->method('format')->willReturnCallback(function (Money $money): string {
            return $money->getAmount();
        });

        return $moneyWrapper;
    }

    private function createGeckoConfig(int $expectTimes = 0): GeckoCoinConfig
    {
        $config = $this->createMock(GeckoCoinConfig::class);

        $config
            ->expects($this->exactly($expectTimes))
            ->method('getCryptos')
            ->willReturn([
                'WEB' => 'webchain',
            ]);

        return $config;
    }
}
