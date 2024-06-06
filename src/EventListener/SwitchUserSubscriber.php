<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    private UserActionLogger $logger;

    public function __construct(UserActionLogger $logger)
    {
        $this->logger = $logger;
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();
        $newView = !$request->getSession()->get('view_only_mode', false);
        $request->getSession()->set('view_only_mode', $newView);

        if ($newView) {
            $this->logger->info('Enter viewonly mode, log in as '.$event->getTargetUser()->getUsername());
        } else {
            $this->logger->info('Leave viewonly mode, switch back to '.$event->getTargetUser()->getUsername());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // constant for security.switch_user
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
