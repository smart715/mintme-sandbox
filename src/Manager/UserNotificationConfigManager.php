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
        $notificationTypes = NotificationTypes::getConfigurable();
        $notificationChannels = NotificationChannels::getAll();

        $userNotificationConfig = $this->userNotificationConfigRepository->getUserNotificationsConfig($user);
        $defaultConfig = [];

        foreach ($notificationTypes as $nType) {
            $defaultConfig[$nType]['text'] = NotificationTypes::getText()[$nType];

            foreach ($notificationChannels as $nChannel) {
                $defaultConfig[$nType]['channels'][$nChannel]['text'] = ucfirst($nChannel);
                $defaultConfig[$nType]['channels'][$nChannel]['value'] = false;

                foreach ($userNotificationConfig as $unc) {
                    $type = $unc->getType();
                    $channel = $unc->getChannel();
                    $defaultConfig[$type]['channels'][$channel]['text'] = ucfirst($channel);
                    $defaultConfig[$type]['channels'][$channel]['value'] = true;
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

        if ($userConfigStored) {
            foreach ($userConfigStored as $userConfig) {
                $this->userNotificationConfigRepository->deleteUserNotificationsConfig($userConfig->getId());
            }
        }

        foreach ($newConfig as $type => $nConfig) {
            foreach ($nConfig['channels'] as $channel => $channelConfig) {
                if ($channelConfig['value']) {
                    $this->createUserNotificationConfig($type, $channel, $user);
                }
            }
        }
    }

    private function createUserNotificationConfig(String $type, String $channel, User $user): void
    {

        $newConfig= (new UserNotificationConfig())
            ->setType($type)
            ->setChannel($channel)
            ->setUser($user);
        $this->em->persist($newConfig);
        $this->em->flush();
    }
}
