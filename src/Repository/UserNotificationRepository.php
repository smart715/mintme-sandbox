<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    public function findUserNotifications(User $user, ?int $notificationLimit): ?array
    {
        $query = $this->createQueryBuilder('user_notification')
            ->where('user_notification.user = :user')
            ->setParameter('user', $user)
            ->orderBy('user_notification.date', 'DESC');

        if (null !== $notificationLimit) {
            $query->setMaxResults($notificationLimit);
        }

        return $query->getQuery()->getResult();
    }
}
