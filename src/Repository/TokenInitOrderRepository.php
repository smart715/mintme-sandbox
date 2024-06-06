<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\TokenInitOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TokenInitOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInitOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInitOrder[]    findAll()
 * @method TokenInitOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class TokenInitOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenInitOrder::class);
    }
}
