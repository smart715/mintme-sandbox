<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Events\RewardEvent;
use Doctrine\Common\Collections\Collection;

/** @codeCoverageIgnore */
class RewardEventActivity extends RewardEvent implements ActivityEventInterface
{
    private int $type;

    public function __construct(Reward $reward, int $type, ?RewardMemberInterface $member, ?Collection $members = null)
    {
        $this->type = $type;

        parent::__construct($reward, $member, $members);
    }

    public function getType(): int
    {
        return $this->type;
    }
}
