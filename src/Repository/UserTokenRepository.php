<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\UserToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserToken::class);
    }

    public function findByUserToken(int $userId, int $tokenId): ?UserToken
    {
        return $this->createQueryBuilder('ut')
            ->andWhere('ut.user = :userId')
            ->andWhere('ut.token = :tokenId')
            ->setParameter('userId', $userId)
            ->setParameter('tokenId', $tokenId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
