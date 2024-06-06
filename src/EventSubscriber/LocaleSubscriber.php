<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private array $transLocales;
    private array $transAuto;
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        string $defaultLocale,
        array $transLocales,
        array $transAuto
    ) {
        $this->defaultLocale = $defaultLocale;
        $this->transLocales = $transLocales;
        $this->transAuto = $transAuto;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
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

        $token = $this->tokenStorage->getToken();

        /** @var User|null $user */
        $user = $token
            ? $token->getUser()
            : null;

        if ($user instanceof User &&
            $locale &&
            $locale !== $user->getLocale() &&
            in_array($locale, $this->transLocales)
        ) {
            $user->setLocale($locale);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    /** @inheritdoc */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest'],
            ],
        ];
    }
}
