<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Rewards\RewardVolunteer;
use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class RewardEvent extends Event implements RewardEventInterface
{
    public const PARTICIPANT_ADDED = "reward.participant_added";
    public const VOLUNTEER_NEW = "reward.volunteer_new";
    public const VOLUNTEER_ACCEPTED = "reward.volunteer_accepted";
    public const VOLUNTEER_COMPLETED = "reward.volunteer_completed";
    public const VOLUNTEER_REJECTED = "reward.volunteer_rejected";
    public const REWARD_DELETED = "reward.reward_deleted";
    public const PARTICIPANT_REJECTED = "reward.participant_rejected";
    public const PARTICIPANT_DELIVERED = "reward.participant_delivered";
    public const PARTICIPANT_REFUNDED = "reward.participant_refunded";
    public const REWARD_NEW = "reward.new";

    protected Reward $reward;
    protected ?RewardMemberInterface $member;
    protected ?Collection $members;

    public function __construct(Reward $reward, ?RewardMemberInterface $member, ?Collection $members = null)
    {
        $this->reward = $reward;
        $this->member = $member;
        $this->members = $members;
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function getRewardMember(): ?RewardMemberInterface
    {
        return $this->member;
    }

    public function getRewardMembers(): array
    {
        return $this->members
            ? $this->members->toArray()
            : [];
    }
}
