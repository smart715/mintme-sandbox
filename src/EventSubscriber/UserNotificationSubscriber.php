<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\UserNotificationEvent;
use App\Manager\UserNotificationManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserNotificationSubscriber implements EventSubscriberInterface
{
    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    public function __construct(UserNotificationManagerInterface $userNotificationManager)
    {
        $this->userNotificationManager = $userNotificationManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserNotificationEvent::NAME => 'createUserNotification',
        ];
    }

    public function createUserNotification(UserNotificationEvent $event): void
    {
        $user = $event->getUser();
        $notificationType =  $event->getNotificationType();
        $extraData = $event->getExtraData();

        $this->userNotificationManager->createNotification($user, $notificationType, $extraData);
    }
}
