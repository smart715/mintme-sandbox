<?php declare(strict_types = 1);

namespace App\Repository\Finance;

use App\Entity\Crypto;
use App\Entity\Finance\FinanceBalance;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class FinanceBalanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceBalance::class);
    }

    /**
     * @return FinanceBalance[]
     */
    public function findBalancesByRange(string $crypto, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.crypto = :crypto')
            ->andWhere('f.timestamp > :from')
            ->andWhere('f.timestamp < :to')
            ->setParameter('crypto', $crypto)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('f.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLatest(?Crypto $crypto = null): array
    {
        $maxResults = 5;

        $queryBuilder = $this->createQueryBuilder('finance')
            ->select('finance');

        if ($crypto) {
            $maxResults = 1;

            $queryBuilder
                ->where('finance.crypto = :crypto')
                ->setParameter('crypto', $crypto->getSymbol());
        }

        return $queryBuilder
            ->orderBy('finance.timestamp', Criteria::DESC)
            ->addOrderBy('finance.id')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}
