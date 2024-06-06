<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use App\Mercure\Authorization as MercureAuthorization;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class KernelSubscriber implements EventSubscriberInterface
{
    private ProfileManagerInterface $profileManager;
    private TokenStorageInterface $tokenStorage;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private bool $isAuth;
    private MercureAuthorization $mercureAuthorization;

    public function __construct(
        bool $isAuth,
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager,
        MercureAuthorization $mercureAuthorization
    ) {
        $this->isAuth = $isAuth;
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->mercureAuthorization = $mercureAuthorization;
    }

    /** @codeCoverageIgnore */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 0],
                ['handleRegisterFormContentOnlyRedirection', 100],
            ],
            KernelEvents::RESPONSE => [
                ['onResponse', -1],
                ['setMercureAuthorizationCookie', 1],
            ],
        ];
    }

    public function handleRegisterFormContentOnlyRedirection(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getSession()->get('registered_form_content_only')) {
            $request->getSession()->remove('registered_form_content_only');
            $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
        }
    }

    /** @codeCoverageIgnore */
    public function onResponse(ResponseEvent $event): void
    {
        //todo: handle this protection through config/packages/nelmio_security.yaml
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');

        // todo: remove this part completely as soon as admin will support CSP
        // https://redmine.abchosting.org/issues/8618
        if (str_starts_with($event->getRequest()->getPathInfo(), '/admin-r8bn')
            && $event->getResponse()->headers->has('Content-Security-Policy')
        ) {
            $csp = $event->getResponse()->headers->get('Content-Security-Policy');
            $csp = preg_replace("/script-src [^;]*;/", "script-src 'self' 'unsafe-inline';", $csp);

            $event->getResponse()->headers->set('Content-Security-Policy', $csp);
        }
    }

    public function setMercureAuthorizationCookie(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if ($this->isMercureAuthorizationCookiePresent($request)) {
            return;
        }

        try {
            $this->mercureAuthorization->setCookie($request, 'public');
        } catch (RuntimeException $e) {
        } // in case cookie was already set in another event
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $csrf = $request->headers->get('X-CSRF-TOKEN', '');

        if (!is_string($csrf) ||
            ($request->isXmlHttpRequest() &&
            $this->isApiRequest($request) &&
            !$this->isCsrfTokenValid($csrf))
        ) {
            $event->setResponse(new Response('Invalid token given.', Response::HTTP_UNAUTHORIZED));

            return;
        }

        /** @psalm-suppress UndefinedDocblockClass */
        if (is_object($this->tokenStorage->getToken()) &&
            is_object($this->tokenStorage->getToken()->getUser()) &&
            !$request->isXmlHttpRequest() &&
            !$this->isImgFilterRequest($request)
        ) {
            /**
             * @var User $user
             * @psalm-suppress UndefinedDocblockClass
             */
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user->getHash()) {
                $this->profileManager->createHash($user, true, $this->isAuth);
            }
        }
    }

    private function isApiRequest(Request $request): bool
    {
        return (bool)preg_match('/^\/api\//', $request->getPathInfo());
    }

    private function isCsrfTokenValid(?string $token): bool
    {
        return $this->csrfTokenManager->isTokenValid(
            new CsrfToken('authenticate', $token ?? '')
        );
    }

    private function isImgFilterRequest(Request $request): bool
    {
        return 'liip_imagine_filter' === $request->attributes->get('_route');
    }

    private function isMercureAuthorizationCookiePresent(Request $request): bool
    {
        return null !== $request->cookies->get('mercureAuthorization');
    }
}
