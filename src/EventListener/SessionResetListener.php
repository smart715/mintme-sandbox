<?php declare(strict_types = 1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionResetListener implements EventSubscriberInterface
{
    private SessionInterface $session;
    private int $sessionLifetime;

    public function __construct(
        SessionInterface $session,
        int $sessionLifetime
    ) {
        $this->session = $session;
        $this->sessionLifetime = $sessionLifetime;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (time() - $this->session->getMetadataBag()->getLastUsed() > $this->sessionLifetime) {
            $this->session->invalidate();
            $this->session->clear();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
