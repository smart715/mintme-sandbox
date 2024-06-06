<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\DeployTokenReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeployTokenReward>
 * @codeCoverageIgnore
 */
class DeployTokenRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeployTokenReward::class);
    }

    public function getReferralProfits(?\DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate): array
    {
        $qb = $this->createQueryBuilder('dtr');
        $qb->select('dtr.currency, SUM(dtr.reward) AS total_reward, COUNT(dtr.id) AS reward_count, dtr.currency');

        if ($startDate) {
            $qb->andWhere('dtr.created >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('dtr.created <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->groupBy('dtr.currency')->getQuery()->getResult();
    }
}
