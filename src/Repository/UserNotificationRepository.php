<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotification;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    /** @return UserNotification[] */
    public function findUserNotifications(User $user, ?int $notificationLimit): ?array
    {
        $actualDate = new DateTimeImmutable();
        $customDate = $actualDate->modify('-'.$notificationLimit.'days');

        $query = $this->createQueryBuilder('user_notification')
            ->where('user_notification.user = :user')
            ->andWhere('user_notification.date BETWEEN :customDate AND :actualDate')
            ->setParameter('user', $user)
            ->setParameter('customDate', $customDate)
            ->setParameter('actualDate', $actualDate)
            ->orderBy('user_notification.date', 'DESC');

        if (null !== $notificationLimit) {
            $query->setMaxResults($notificationLimit);
        }

        return $query->getQuery()->getResult();
    }
}
