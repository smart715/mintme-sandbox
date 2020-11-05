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
        return $this->configNormalizer(
            $this->userNotificationConfigRepository->getUserNotificationsConfig($user)
        );
    }

    public function updateUserNotificationsConfig(
        User $user,
        NotificationTypes $notificationTypes,
        NotificationChannels $notificationsChannel
    ): void {
        // todo update the table
    }

    private function configNormalizer(?array $userNotificationsConfig): ?array
    {
        $notificationsTypes = NotificationTypes::getAll();
        $notificationsChannels = NotificationChannels::getAll();

        $result = [];
        /*foreach ($userNotificationsConfig as $unConfig) {
            $result[] = [
                $unConfig->getType
            ]
        }*/
       // dd($userNotificationsConfig);
        foreach ($notificationsTypes as $key => $nType) {
            foreach ($notificationsChannels as $nChannel) {
               // dd($userNotificationsConfig);
                //dd(in_array('website', $userNotificationsConfig));
                $result[$nType][$nChannel] =
                /*$result[$nType][$nChannel] = *///in_array($nChannel, $userNotificationsConfig, true) && in_array($nType, $userNotificationsConfig, true);
                /*$result[$nType][$nChannel][] =  array_walk($userNotificationsConfig, static function (object $data) use (&$nType, &$nChannel): bool {
                            return $data->getType() === $nType && $data->getChannel() === $nChannel;
                });*/
            }

            /*$result[$nType][] = [
                $nChannel => array_walk(
                    $userNotificationsConfig, function ($ntype, $nChannel)
                )
            ];*/
        }

         dd($result);
        //dd($userNotificationsConfig->geTtype());
    }
}
