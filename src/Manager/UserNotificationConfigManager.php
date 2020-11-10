<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserNotificationConfigRepository;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationConfigManager implements UserNotificationConfigManagerInterface
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var UserNotificationConfigRepository */
    private UserNotificationConfigRepository $userNotificationConfigRepository;


    public function __construct(
        EntityManagerInterface $em,
        UserNotificationConfigRepository $userNotificationConfigRepository
    ) {
        $this->em = $em;
        $this->userNotificationConfigRepository = $userNotificationConfigRepository;
    }

    public function getUserNotificationsConfig(User $user): ?array
    {
      //  return $this->userNotificationConfigRepository->getUserNotificationsConfig($user);

        $notificationTypes = NotificationTypes::getAll();
        $notificationChannels = NotificationChannels::getAll();

        $userNotificationConfig = $this->userNotificationConfigRepository->getUserNotificationsConfig($user);
        $defaultConfig = [];

        foreach ($notificationTypes as $nType) {
            $defaultConfig[$nType]['text'] = NotificationTypes::getText()[$nType];

            foreach ($notificationChannels as $nChannel) {
                $defaultConfig[$nType][$nChannel]['text'] = ucfirst($nChannel);
                $defaultConfig[$nType][$nChannel]['value'] = false;

                foreach ($userNotificationConfig as $unc) {
                    $type = $unc->getType();
                    $channel = $unc->getChannel();
                    $defaultConfig[$type][$channel]['text'] = ucfirst($channel);
                    $defaultConfig[$type][$channel]['value'] = true;
                }
            }
        }

        return $defaultConfig;
    }

    public function updateUserNotificationsConfig(
        User $user,
        NotificationTypes $notificationTypes,
        NotificationChannels $notificationsChannel
    ): void {
        // todo update the table
    }
}
