<?php declare(strict_types = 1);

namespace App\Exchange\Config;

/** @codeCoverageIgnore */
class DeployCostConfig
{
    /** @var int $deployCost */
    private $deployCost;

    /** @var float */
    private $deployCostReward;

    public function __construct(int $deployCost, float $deployCostReward)
    {
        $this->deployCost = $deployCost;
        $this->deployCostReward = $deployCostReward;
    }

    public function getDeployCost(): int
    {
        return $this->deployCost;
    }

    public function getDeployCostReward(): float
    {
        return $this->deployCostReward * 100;
    }

    public function calculateReward(): float
    {
        return $this->deployCost * $this->deployCostReward;
    }
}
