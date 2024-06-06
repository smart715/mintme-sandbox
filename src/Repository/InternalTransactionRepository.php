<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\InternalTransaction\InternalTransaction;
use App\Entity\User;
use App\Wallet\Model\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InternalTransaction>
 * @codeCoverageIgnore
 */
class InternalTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternalTransaction::class);
    }

    /** @return InternalTransaction[] */
    public function getLatest(User $user, int $offset, int $limit, \DateTimeImmutable $fromDate): array
    {
        return $this->createQueryBuilder('it')
            ->where('it.user = :user')
            ->andWhere('it.date > :fromDate')
            ->orderBy('it.date', Criteria::DESC)
            ->setParameter('user', $user)
            ->setParameter('fromDate', $fromDate)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getInternalTransactionsPerCrypto(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('it')
            ->select('SUM(it.fee) as withdrawalsFee, count(it.id) as withdrawalCount, c.symbol as network')
            ->join('it.cryptoNetwork', 'c')
            ->where('it.date >= :startDate')
            ->andWhere('it.date <= :endDate')
            ->andWhere('it.type = :type')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('type', Type::WITHDRAW)
            ->groupBy('it.cryptoNetwork')
            ->getQuery()
            ->getResult();
    }
}
