<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Bonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

 /** @codeCoverageIgnore */
class BonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bonus::class);
    }

    public function getPaidSum(string $type): ?string
    {
        return $this->createQueryBuilder('bonus')
            ->select('COALESCE(SUM(bonus.quantity), 0) as totalBonus')
            ->where('bonus.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult()[0]['totalBonus'];
    }
}
