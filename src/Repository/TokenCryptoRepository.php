<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenCrypto>
 * @codeCoverageIgnore
 */
class TokenCryptoRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenCrypto::class);
    }

    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array {
        return $this->createQueryBuilder('tc')
            ->join('tc.token', 't')
            ->join('tc.crypto', 'c')
            ->where('t.profile = :profile')
            ->andWhere('c.symbol != :crypto')
            ->andWhere('tc.created > :fromDate')
            ->setParameter('profile', $user->getProfile())
            ->setParameter('crypto', Symbols::WEB)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('tc.created', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCostPerCrypto(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('tc')
            ->select('SUM(tc.cost) as totalCost, count(tc.id) as count, cc.symbol')
            ->join('tc.cryptoCost', 'cc')
            ->where('tc.created >= :startDate')
            ->andWhere('tc.created <= :endDate')
            ->andWhere('tc.cryptoCost IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('cc.symbol')
            ->getQuery()
            ->getResult();
    }
}
