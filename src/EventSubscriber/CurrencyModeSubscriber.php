<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** @codeCoverageIgnore */
class CurrencyModeSubscriber implements EventSubscriberInterface
{
    /** @var String */
    private String $default_currency_mode;

    public function __construct(String $default_currency_mode)
    {
        $this->default_currency_mode = $default_currency_mode;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {

        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            $request->getSession()->set('_currency_mode', $this->default_currency_mode);
        }

        if ($currencyMode = $request->attributes->get('mode')) {
            $request->getSession()->set('_currency_mode', $currencyMode);
        }
    }
}
