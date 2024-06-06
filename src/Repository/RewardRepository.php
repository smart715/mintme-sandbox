<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reward>
 * @codeCoverageIgnore
 */
class RewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reward::class);
    }

    public function getRewardByCreatedAtDay(string $type, \DateTimeImmutable $date): array
    {
        $from = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return $this->createQueryBuilder('reward')
            ->where('reward.createdAt BETWEEN :from AND :to')
            ->andWhere('reward.type = :type')
            ->andWhere('reward.status = :status')
            ->setParameter('type', $type)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', Reward::STATUS_ACTIVE)
            ->orderBy('reward.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getRewardsByCreatedAtDayAndToken(Token $token, string $type, \DateTimeImmutable $date): array
    {
        $from = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return $this->createQueryBuilder('reward')
            ->where('reward.token = :token')
            ->andWhere('reward.createdAt BETWEEN :from AND :to')
            ->andWhere('reward.type = :type')
            ->andWhere('reward.status = :status')
            ->setParameter('token', $token)
            ->setParameter('type', $type)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', Reward::STATUS_ACTIVE)
            ->orderBy('reward.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Reward[] */
    public function getActiveRewards(Token $token): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.participants', 'p')
            ->leftJoin('r.volunteers', 'v')
            ->addSelect(['p', 'v'])
            ->where('r.token = :token')
            ->andWhere('r.status = :status')
            ->setParameter('token', $token)
            ->setParameter('status', Reward::STATUS_ACTIVE)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
