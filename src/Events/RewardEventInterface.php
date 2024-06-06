<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardVolunteer;

interface RewardEventInterface
{
    public function getReward(): Reward;
    public function getRewardMember(): ?RewardMemberInterface;
}
