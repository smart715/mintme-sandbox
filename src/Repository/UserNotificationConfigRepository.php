<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserNotificationConfig;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class UserNotificationConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationConfig::class);
    }

    public function getUserNotificationsConfig(User $user): ?array
    {
        return $this->createQueryBuilder('unc')
            ->where('unc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    public function getOneUserNotificationsConfig(User $user, string $type, string $channel): array
    {
        return $this->createQueryBuilder('unc')
            ->where('unc.user = :user')
            ->andWhere('unc.type = :type')
            ->andWhere('unc.channel = :channel')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getResult();
    }

    public function deleteUserNotificationsConfig(int $userNotificationConfigId): int
    {
        return $this->createQueryBuilder('unc')
            ->delete()
            ->where('unc.id = :id')
            ->setParameter('id', $userNotificationConfigId)
            ->getQuery()
            ->execute();
    }
    public function getDisabledUserPostNotificationConfigByToken(User $user, Token $token): array
    {
        return $this->findBy([
            'user' => $user,
            'type' => NotificationTypes::TOKEN_NEW_POST,
            'channel' => NotificationChannels::ADVANCED,
            'token' => $token,
            'tokenPostEnabled' => false,
        ]);
    }
}
