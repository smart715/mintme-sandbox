<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserChangeEmailRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserChangeEmailRequest>
 * @codeCoverageIgnore
*/
class UserChangeEmailRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserChangeEmailRequest::class);
    }

    public function findLastActiveRequest(User $user): ?UserChangeEmailRequest
    {
        return $this->createQueryBuilder('cer')
            ->where('cer.user = :userId')
            ->andWhere('cer.confirmedAt IS NULL')
            ->setParameter('userId', $user->getId())
            ->orderBy('cer.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0] ?? null;
    }

    /** @return UserChangeEmailRequest[] */
    public function findExpiredRequestsForUser(User $user, \DateTimeImmutable $expiredTime): array
    {
        return $this->createQueryBuilder('cer')
            ->where('cer.user = :userId')
            ->andWhere('cer.confirmedAt IS NULL')
            ->andWhere('cer.createdAt <= :expiredTime')
            ->setParameter('userId', $user->getId())
            ->setParameter('expiredTime', $expiredTime)
            ->getQuery()
            ->getResult();
    }
}
