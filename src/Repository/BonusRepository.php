<?php declare(strict_types = 1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BonusRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function getPaidSum(string $type): int
    {
        return (int)$this->createQueryBuilder('bonus')
            ->select('SUM(bonus.quantityWeb)')
            ->where('bonus.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
