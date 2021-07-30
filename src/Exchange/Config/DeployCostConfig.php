<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;

/** @codeCoverageIgnore */
class DeployCostConfig
{
    private array $costs;
    private array $fees;
    private array $rewardCosts;

    public function __construct(
        float $deployCostMintme,
        float $deployCostEth,
        float $deployCostBnb,
        float $deployFeeEth,
        float $deployFeeBnb,
        float $deployCostRewardMintme,
        float $deployCostRewardEth,
        float $deployCostRewardBnb
    ) {
        $this->costs = [
            Symbols::WEB => $deployCostMintme,
            Symbols::ETH => $deployCostEth,
            Symbols::BNB => $deployCostBnb,
        ];
        $this->fees = [
            Symbols::ETH => $deployFeeEth,
            Symbols::BNB => $deployFeeBnb,
        ];
        $this->rewardCosts = [
            Symbols::WEB => $deployCostRewardMintme,
            Symbols::ETH => $deployCostRewardEth,
            Symbols::BNB => $deployCostRewardBnb,
        ];
    }

    public function getDeployFee(string $symbol): float
    {
        return $this->fees[$symbol] ?? 0;
    }

    public function getDeployCost(string $symbol): float
    {
        if (!isset($this->costs[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->costs[$symbol];
    }

    public function getDeployCostReward(string $symbol): float
    {
        if (!isset($this->rewardCosts[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->rewardCosts[$symbol];
    }

    public function getDeployCostRewardPercent(string $symbol): float
    {
        if (!isset($this->rewardCosts[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->rewardCosts[$symbol] * 100;
    }

    public function getSymbols(): array
    {
        return array_keys($this->costs);
    }
}
