<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAction>
 * @codeCoverageIgnore
 */
class UserActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAction::class);
    }

    public function getCountByUserAtDate(User $user, string $action, \DateTimeImmutable $date): int
    {
        $from = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return (int)$this->createQueryBuilder('ua')
            ->select('COUNT(ua.id)')
            ->where('ua.user = :user')
            ->andWhere('ua.action = :action')
            ->andWhere('ua.createdAt BETWEEN :from AND :to')
            ->setParameter('user', $user->getId())
            ->setParameter('action', $action)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();
    }
}
