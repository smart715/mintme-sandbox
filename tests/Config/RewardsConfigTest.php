<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\RewardsConfig;
use App\Entity\Rewards\Reward;
use PHPUnit\Framework\TestCase;

class RewardsConfigTest extends TestCase
{
    public function testGetMaxLimit(): void
    {
        $rewardsConfig = new RewardsConfig([
            'rewards_max_limit' => 10,
            'bounties_max_limit' => 15,
        ]);

        $this->assertEquals(10, $rewardsConfig->getMaxLimit(Reward::TYPE_REWARD));
        $this->assertEquals(15, $rewardsConfig->getMaxLimit(Reward::TYPE_BOUNTY));
    }
}
