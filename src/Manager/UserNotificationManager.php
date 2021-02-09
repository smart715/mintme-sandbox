<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Exception\ApiBadRequestException;
use App\Repository\UserNotificationRepository;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationManager implements UserNotificationManagerInterface
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var UserNotificationRepository */
    private UserNotificationRepository $userNotificationRepository;

    /** @var UserNotificationConfigManagerInterface */
    private UserNotificationConfigManagerInterface $notificationConfigManager;

    public function __construct(
        EntityManagerInterface $em,
        UserNotificationRepository $userNotificationRepository,
        UserNotificationConfigManagerInterface $notificationConfigManager
    ) {
        $this->em = $em;
        $this->userNotificationRepository =  $userNotificationRepository;
        $this->notificationConfigManager = $notificationConfigManager;
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
        return $this->userNotificationRepository->findUserNotifications($user, $notificationLimit);
    }

    public function updateNotifications(User $user, ?int $notificationLimit): void
    {
        $notifications =  $this->userNotificationRepository->findUserNotifications($user, $notificationLimit);

        if (!$notifications) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        foreach ($notifications as $notification) {
            $notification->setViewed(true);
            $this->em->persist($notification);
        }

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
