<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ScheduledNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScheduledNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledNotification::class);
    }

    public function getAll(): ?array
    {
        return $this->createQueryBuilder('sn')
            ->where('sn.user IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function deleteScheduledNotification(int $scheduledNotificationId): int
    {
        return $this->createQueryBuilder('sn')
            ->delete()
            ->where('sn.id = :id')
            ->setParameter('id', $scheduledNotificationId)
            ->getQuery()
            ->execute();
    }

    public function findByUser(User $user): ?ScheduledNotification
    {
        return $this->findOneBy(['user' => $user]);
    }
}
