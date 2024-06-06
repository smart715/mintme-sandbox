<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\InactiveOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InactiveOrder>
 * @method InactiveOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method InactiveOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method InactiveOrder[]    findAll()
 * @method InactiveOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class InactiveOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InactiveOrder::class);
    }
}
