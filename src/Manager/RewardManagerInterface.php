<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Rewards\RewardVolunteer;
use App\Entity\Token\Token;
use App\Entity\User;
use Money\Money;

interface RewardManagerInterface
{
    public function getUnfinishedRewardsByToken(Token $token): array;

    public function getBySlug(string $slug, bool $onlyActive = true): ?Reward;

    public function createReward(Reward $reward): void;

    public function deleteReward(Reward $reward): void;

    public function saveReward(Reward $reward, Money $oldPrice, int $oldQuantity): void;

    public function addMember(RewardMemberInterface $member): Reward;

    public function acceptMember(RewardVolunteer $member): Reward;

    public function completeMember(RewardParticipant $member): Reward;

    public function findMember(User $user, Reward $reward): ?RewardMemberInterface;

    public function findMemberById(int $id, Reward $reward): ?RewardMemberInterface;

    public function rejectVolunteer(RewardVolunteer $rewardVolunteer): Reward;

    public function removeParticipant(RewardMemberInterface $participant): Reward;
    
    public function refundReward(Reward $reward, RewardParticipant $participant): Reward;

    public function setParticipantStatus(RewardParticipant $participant, string $status): RewardParticipant;
}
