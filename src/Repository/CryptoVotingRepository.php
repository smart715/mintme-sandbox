<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Voting\CryptoVoting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class CryptoVotingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CryptoVoting::class);
    }

    public function getVotingByCryptoId(int $cryptoId, int $offset, int $limit): ?array
    {
        $query = $this->createQueryBuilder('v')
            ->where('v INSTANCE OF App\Entity\Voting\CryptoVoting')
            ->andWhere('v.crypto = :crypto')
            ->leftJoin('v.options', 'o')
            ->leftJoin('v.userVotings', 'uv')
            ->addSelect(['o', 'uv'])
            ->orderBy('v.createdAt', 'DESC')
            ->setParameter('crypto', $cryptoId)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query);

        /** @phpstan-ignore-next-line calling getArrayCopy() on an Iterator is undefined, but it's defined */
        return $paginator->getIterator()->getArrayCopy();
    }

    public function getVotingCountAll(): int
    {
        return (int)$this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v)')
            ->where('v INSTANCE OF App\Entity\Voting\CryptoVoting')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenVotings(): int
    {
        return (int)$this->createQueryBuilder('v')
            ->select('count(v.id)')
            ->where('v INSTANCE OF App\Entity\Voting\CryptoVoting')
            ->where('v.endDate > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
