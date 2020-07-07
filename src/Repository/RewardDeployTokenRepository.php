<?php

namespace App\Repository;

use App\Entity\RewardDeployToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RewardDeployToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method RewardDeployToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method RewardDeployToken[]    findAll()
 * @method RewardDeployToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RewardDeployTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardDeployToken::class);
    }

    // /**
    //  * @return RewardDeployToken[] Returns an array of RewardDeployToken objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RewardDeployToken
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
