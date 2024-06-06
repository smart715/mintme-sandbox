<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\TokenEventInterface;
use App\Events\TokenEvents;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Utils\NotificationTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TokenCreatedSubscriber implements EventSubscriberInterface
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
            TokenEvents::CREATED => 'handleEvent',
        ];
    }

    public function handleEvent(TokenEventInterface $event): void
    {
        $token = $event->getToken();

        $this->scheduledNotificationManager->createScheduledNotification(
            NotificationTypes::MARKETING_AIRDROP_FEATURE,
            $token->getOwner(),
            false,
        );

        $this->scheduledNotificationManager->createScheduledTokenNotification(
            NotificationTypes::TOKEN_PROMOTION,
            $token,
        );
    }
}
