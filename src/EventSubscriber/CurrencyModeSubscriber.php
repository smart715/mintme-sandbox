<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** @codeCoverageIgnore */
class CurrencyModeSubscriber implements EventSubscriberInterface
{
    /** @var String */
    private String $default_currency_mode;

    /** @var SessionInterface */
    private SessionInterface $session;

    public function __construct(String $default_currency_mode, SessionInterface $session)
    {
        $this->default_currency_mode = $default_currency_mode;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($currencyMode = $request->attributes->get('mode')) {
            $request->getSession()->set('_currency_mode', $currencyMode);
        }
    }

    public function onKernelResponse(): void
    {
        if (null === $this->session->get('_currency_mode')) {
            $this->session->set('_currency_mode', $this->default_currency_mode);

        }
    }
}
