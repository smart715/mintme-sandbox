<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\UserNotificationEvent;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\WithdrawalStrategy;
use App\Utils\NotificationTypes;
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
        $strategy = NotificationTypes::getStrategyText()[$notificationType].'Strategy';
        $strategy = new $strategy($user, $notificationType);
        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($user);
        $this->userNotificationManager->createNotification($user, $notificationType, $extraData);
    }
}
