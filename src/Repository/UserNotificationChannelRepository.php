<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotificationChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserNotificationChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationChannel::class);
    }

    public function getUserNotificationsChannel(User $user): ?array
    {
        return $this->createQueryBuilder('unc')
            ->where('unc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();
    }
}
