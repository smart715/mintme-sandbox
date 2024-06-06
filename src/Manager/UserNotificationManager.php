<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\NotificationInterface;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\BroadcastNotificationRepository;
use App\Repository\UserNotificationRepository;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationManager implements UserNotificationManagerInterface
{
    private EntityManagerInterface $em;
    private UserNotificationRepository $userNotificationRepository;
    private UserNotificationConfigManagerInterface $notificationConfigManager;
    private BroadcastNotificationRepository $broadcastNotificationRepository;
    public function __construct(
        EntityManagerInterface $em,
        UserNotificationRepository $userNotificationRepository,
        UserNotificationConfigManagerInterface $notificationConfigManager,
        BroadcastNotificationRepository $broadcastNotificationRepository
    ) {
        $this->em = $em;
        $this->userNotificationRepository =  $userNotificationRepository;
        $this->notificationConfigManager = $notificationConfigManager;
        $this->broadcastNotificationRepository = $broadcastNotificationRepository;
    }

    public function createNotification(
        User $user,
        String $notificationType,
        ?array $extraData
    ): void {
        $userNotification = (new UserNotification())
            ->setType($notificationType)
            ->setUser($user);

        if ($extraData) {
            $userNotification->setJsonData($extraData);
        }

        $userNotification->setViewed(false);
        $this->em->persist($userNotification);
        $this->em->flush();
    }

    public function getNotifications(User $user, ?int $notificationLimit): ?array
    {
        $userNotifications = $this->userNotificationRepository->findUserNotifications($user, $notificationLimit);
        $broadcastNotifications = $this->broadcastNotificationRepository->findLatest($notificationLimit);

        $mergedNotifications = array_merge($userNotifications, $broadcastNotifications);
        usort($mergedNotifications, static function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return array_map(static function (NotificationInterface $notification) use ($user): NotificationInterface {
            return $notification->setViewed($user->getLastNotificationCheck() >= $notification->getDate());
        }, $mergedNotifications);
    }

    public function updateNotifications(User $user): void
    {
        $user->setLastNotificationCheck(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();
    }

    public function isNotificationAvailable(User $user, String $type, String $channel): Bool
    {
        if (NotificationTypes::ORDER_FILLED === $type || NotificationTypes::ORDER_CANCELLED === $type) {
            return true;
        }

        return !empty($this->notificationConfigManager->getOneUserNotificationConfig($user, $type, $channel));
    }
}
