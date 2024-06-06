<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use App\Entity\Token\TokenDeploy;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenDeploy>
 * @codeCoverageIgnore
 */
class TokenDeployRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenDeploy::class);
    }

    public function findByAddress(string $address): ?TokenDeploy
    {
        return $this->findOneBy(['address' => $address]);
    }

    public function findByAddressAndCrypto(string $address, Crypto $crypto): ?TokenDeploy
    {
        return $this->findOneBy(['address' => $address, 'crypto' => $crypto]);
    }

    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array {
        return $this->createQueryBuilder('d')
            ->join('d.token', 't')
            ->where('t.profile = :profile')
            ->andWhere('d.createdAt > :fromDate')
            ->setParameter('profile', $user->getProfile())
            ->setParameter('fromDate', $fromDate)
            ->orderBy('d.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCostPerCrypto(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('d')
            ->select('SUM(d.deployCost) as totalCost, count(d.id) as count, c.symbol')
            ->join('d.crypto', 'c')
            ->where('d.createdAt >= :startDate')
            ->andWhere('d.createdAt <= :endDate')
            ->andWhere('d.deployCost IS NOT NULL')
            ->andWhere('d.deployCost != 0')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('c.symbol')
            ->getQuery()
            ->getResult();
    }
}
