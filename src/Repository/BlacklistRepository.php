<?php declare(strict_types = 1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BlacklistRepository extends EntityRepository
{
    /** @codeCoverageIgnore
     * @param string $value
     * @param string $type
     * @param bool $isSensitive
     * @return bool
     */
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
}
