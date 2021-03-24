<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\AirdropEvent;
use App\Events\TokenEvents;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Utils\NotificationTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AirdropCreatedSubscriber implements EventSubscriberInterface
{
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;

    public function __construct(
        ScheduledNotificationManagerInterface $scheduledNotificationManager
    ) {
        $this->scheduledNotificationManager = $scheduledNotificationManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CREATED => 'removeScheduledNotification',
        ];
    }

    public function removeScheduledNotification(AirdropEvent $event): void
    {
        $this->scheduledNotificationManager->removeByTypeForUser(
            NotificationTypes::MARKETING_AIRDROP_FEATURE,
            $event->getToken()->getOwner()
        );
    }
}
