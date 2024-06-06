<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitServiceModel;
use App\Manager\TokenDeployManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;

class DeploymentProfitFetcher extends AbstractProfitFetcher
{
    private TokenDeployManagerInterface $tokenDeployManager;

    public function __construct(
        TokenDeployManagerInterface $tokenDeployManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->tokenDeployManager = $tokenDeployManager;
    }

    public static function profitModel(): string
    {
        return ProfitServiceModel::class;
    }

    /** @return ProfitServiceModel[]|AbstractProfitModel[] */
    public function fetch(DateTimeImmutable $startDate, DateTimeImmutable $endDate, bool $withMintMe = true): array
    {
        $totalProfits = $this->tokenDeployManager->getTotalCostPerCrypto($startDate, $endDate);

        if (!$withMintMe) {
            $totalProfits = array_filter($totalProfits, fn(array $cryptoData) => !$this->isWEB($cryptoData['symbol']));
        }

        $deployedProfits = array_map(function (array $deploymentsProfits) {
            $crypto = $deploymentsProfits['symbol'];
            $count = $deploymentsProfits['count'];
            $totalCost = $deploymentsProfits['totalCost'];

            $profit = $this->formatMoneyWithNotation($totalCost, $crypto);
            $profitInUsd = $this->calculateUsdValue($profit, $crypto);

            return new ProfitServiceModel($crypto, $count, $profit, $profitInUsd);
        }, $totalProfits);

        return $this->appendTotalColumn($deployedProfits);
    }
}
