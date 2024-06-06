<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;

/** @codeCoverageIgnore */
class DeployCostConfig
{
    private array $deployCosts;
    private array $deployFees;
    private array $deployReferralReward;

    public function __construct(
        array $deployCosts,
        array $deployFees,
        array $deployReferralRewards
    ) {
        $deployCosts[Symbols::WEB] = $deployCosts[Symbols::MINTME];
        $deployFees[Symbols::WEB] = 0;
        $deployReferralRewards[Symbols::WEB] = $deployReferralRewards[Symbols::MINTME];

        $this->deployCosts = $deployCosts;
        $this->deployFees = $deployFees;
        $this->deployReferralReward = $deployReferralRewards;
    }

    public function getDeployFee(string $symbol): float
    {
        return $this->deployFees[$symbol] ?? 0;
    }

    public function getDeployCost(string $symbol): float
    {
        if (!isset($this->deployCosts[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->deployCosts[$symbol];
    }

    public function getDeployCostReward(string $symbol): float
    {
        if (!isset($this->deployReferralReward[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->deployReferralReward[$symbol];
    }

    public function getDeployCostRewardPercent(string $symbol): float
    {
        if (!isset($this->deployReferralReward[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->deployReferralReward[$symbol] * 100;
    }

    public function getSymbols(): array
    {
        return array_keys($this->deployCosts);
    }
}
