<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardVolunteer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RewardVolunteer>
 * @codeCoverageIgnore
 */
class RewardVolunteerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardVolunteer::class);
    }

    public function findVolunteerByUserAndReward(User $user, Reward $reward): ?RewardVolunteer
    {
        return $this->findOneBy(['user' => $user, 'reward' => $reward]);
    }

    public function findVolunteerById(int $id): ?RewardVolunteer
    {
        return $this->findOneBy(['id' => $id]);
    }
}
