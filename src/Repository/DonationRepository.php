<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function findUserRelated(User $user, int $offset, int $limit, \DateTimeImmutable $fromDate): array
    {
        $result = $this->createQueryBuilder('donation')
            ->where('donation.donor = :user')
            ->orWhere('donation.tokenCreator = :user')
            ->andWhere('donation.createdAt > :fromDate')
            ->setParameter('user', $user)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('donation.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $result->getQuery()->getResult();
    }

    public function getAllDirectBuy(Token $token): array
    {
        return $this
            ->createQueryBuilder('d')
            ->select('d.tokenAmount')
            ->where('d.token = :token')
            ->andWhere('d.type = :type')
            ->setParameter('token', $token)
            ->setParameter('type', Donation::TYPE_FULL_BUY)
            ->getQuery()
            ->getResult();
    }

    public function getUserDonationRewards(User $user): array
    {
        return $this
            ->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.referencerAmount), 0) as referencer_amount')
            ->where('d.referencer = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getTotalRewardsGiven(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this
            ->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.referencerAmount), 0) as referencer_amount, COUNT(d.id) as count')
            ->where('d.createdAt >= :from')
            ->andWhere('d.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()[0];
    }
}
