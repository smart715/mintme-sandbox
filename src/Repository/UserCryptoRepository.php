<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use App\Entity\UserCrypto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class UserCryptoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCrypto::class);
    }

    public function getUserCrypto(Crypto $crypto, array $userIds): array
    {
        $qb = $this->createQueryBuilder('q');

        return $qb->select('uc')
            ->innerJoin('uc.user', 'u')
            ->innerJoin('u.profile', 'p')
            ->add('where', $qb->expr()->in('uc.user', $userIds))
            ->andWhere('uc.crypto = ?1')
            ->andWhere('p.anonymous = 0')
            ->setParameter(1, $crypto->getId())
            ->getQuery()
            ->execute();
    }
}
