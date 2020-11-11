<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserNotificationConfig;
use App\Repository\UserNotificationConfigRepository;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

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
        Request $request
    ): void {
        $newConfig = $request->request->all();
        $userConfigStored = $this->userNotificationConfigRepository->getUserNotificationsConfig($user);

        // todo foreach new config...
//dd($newConfig);
        // Delete old Config
        /*foreach ($userConfigStored as $userConfig) {
            $this->userNotificationConfigRepository->deleteUserNotificationsConfig($userConfig->getId());
        }*/
        // Insert new Config
        $notificationChannels = NotificationChannels::getAll();
        foreach ($newConfig as $index=> $nConfig) {
           //dd($index); //type
            /*if ($index === 'text') {
                continue;
            }*/
          //  dd(array_keys($nConfig[$notificationChannels[$index]]));
            //$this->createUserNotificationConfig($type, $channel , $user);
        }
    }
}
