<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private array $transLocales;
    private array $transAuto;

    public function __construct(string $defaultLocale, array $transLocales, array $transAuto)
    {
        $this->defaultLocale = $defaultLocale;
        $this->transLocales = $transLocales;
        $this->transAuto = $transAuto;
    }

    /** @codeCoverageIgnore */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $browserLang = $request->getPreferredLanguage();

        if (!$request->getSession()->get('_locale') &&
            in_array($browserLang, $this->transAuto, true) &&
            in_array($browserLang, $this->transLocales)) {
            $request->getSession()->set('_locale', $browserLang);
            $urlLocale = $request->attributes->get('_locale');

            if ($browserLang !== $urlLocale) {
                $pathInfo = '/'.$browserLang.$pathInfo;
            }

            $event->setResponse(new RedirectResponse($pathInfo));
        }

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    /** @inheritdoc */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 17],
            ],
        ];
    }
}
