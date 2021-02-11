<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class KernelSubscriber implements EventSubscriberInterface
{
    /** @var ProfileManagerInterface */
    private $profileManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var bool */
    private $isAuth;

    private AuthorizationCheckerInterface $security;

    private UrlGeneratorInterface $urlGenerator;

    private const PATHS_REQUIRED_AUTH = [
        'token_create',
        'wallet',
        'chat',
        'profile',
    ];

    public function __construct(
        bool $isAuth,
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager,
        AuthorizationCheckerInterface $security,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->isAuth = $isAuth;
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    /** @codeCoverageIgnore */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    /** @codeCoverageIgnore */
    public function onResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
        $event->getResponse()->headers->set('X-Frame-Options', 'deny');
    }

    public function onRequest(RequestEvent $request): void
    {
        $csrf = $request->getRequest()->headers->get('X-CSRF-TOKEN', '');

        if (in_array($request->getRequest()->attributes->get('_route'), self::PATHS_REQUIRED_AUTH) &&
            !$this->security->isGranted('ROLE_USER')
        ) {
            $request->setResponse(new RedirectResponse($this->urlGenerator->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL)));

            return;
        }

        if (!is_string($csrf) ||
            ($request->getRequest()->isXmlHttpRequest() &&
            $this->isApiRequest($request->getRequest()) &&
            !$this->isCsrfTokenValid($csrf))
        ) {
            $request->setResponse(new Response('Invalid token given.', Response::HTTP_UNAUTHORIZED));

            return;
        }

        /** @psalm-suppress UndefinedDocblockClass */
        if (is_object($this->tokenStorage->getToken()) &&
            is_object($this->tokenStorage->getToken()->getUser()) &&
            !$request->getRequest()->isXmlHttpRequest() &&
            !$this->isImgFilterRequest($request->getRequest())
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
}
