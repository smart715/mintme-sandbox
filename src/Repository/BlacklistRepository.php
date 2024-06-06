<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Blacklist\Blacklist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blacklist>
 * @codeCoverageIgnore
 */
class BlacklistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blacklist::class);
    }

    public function matchValue(string $value, string $type, bool $isSensitive = true): bool
    {
        $valCondition = $isSensitive ?
            "UPPER(t.value) = UPPER(:value)" :
            "t.value = :value";

        return isset($this->createQueryBuilder('t')
            ->where($valCondition)
            ->andWhere("t.type = :type")
            ->setParameter('value', $value)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult()[0]);
    }

    public function bulkDelete(string $type): int
    {
        return $this->createQueryBuilder('b')
            ->delete()
            ->where('b.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();
    }

    public function getValues(string $type): array
    {
        return array_column($this->createQueryBuilder('b')
            ->select('b.value')
            ->where('b.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult(), 'value');
    }
}
