<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/** @codeCoverageIgnore */
class CurrencyModeSubscriber implements EventSubscriberInterface
{
    /** @var Environment */
    private Environment $defaultCurrencyMode;

    public function __construct(Environment $defaultCurrencyMode)
    {
        $this->defaultCurrencyMode = $defaultCurrencyMode;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($currencyMode = $request->attributes->get('_currencyMode')) {
            $request->getSession()->set('_currencyMode', $currencyMode);
        } else {
            $request->setLocale($request->getSession()->get('_currencyMode', $this->defaultCurrencyMode));
        }
    }
}
