<?php declare(strict_types = 1);

namespace App\Config;

use App\Entity\Rewards\Reward;

class RewardsConfig
{
    private array $rewardsConfig;

    public function __construct(array $rewardsConfig)
    {
        $this->rewardsConfig = $rewardsConfig;
    }

    public function getMaxLimit(string $type): int
    {
        return $this->rewardsConfig[Reward::TYPE_REWARD === $type ? 'rewards_max_limit' : 'bounties_max_limit'];
    }
}
