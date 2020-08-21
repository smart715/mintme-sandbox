<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class KernelSubscriber implements EventSubscriberInterface
{
    /** @var ProfileManagerInterface  */
    private $profileManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var bool */
    private $isAuth;

    /** @var ParameterBagInterface */
    private $bag;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        bool $isAuth,
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager,
        ParameterBagInterface $bag,
        SessionInterface $session
    ) {
        $this->isAuth = $isAuth;
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->bag = $bag;
        $this->session = $session;
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
    public function onResponse(FilterResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
        $event->getResponse()->headers->set('X-Frame-Options', 'deny');
    }

    public function onRequest(GetResponseEvent $request): void
    {
        if ($this->bag->get('is_hacker_allowed') && null === $this->session->get('show_info_bar')) {
            $this->session->set('show_info_bar', true);
        }

        $csrf = $request->getRequest()->headers->get('X-CSRF-TOKEN', '');

        if (!is_string($csrf) ||
            ($request->getRequest()->isXmlHttpRequest() &&
            $this->isApiRequest($request->getRequest()) &&
            !$this->isCsrfTokenValid($csrf))
        ) {
            throw new AccessDeniedHttpException("Invalid token given");
        }

        /** @psalm-suppress UndefinedDocblockClass */
        if (is_object($this->tokenStorage->getToken()) &&
            is_object($this->tokenStorage->getToken()->getUser()) &&
            !$request->getRequest()->isXmlHttpRequest()
        ) {
            /**
             * @var User $user
             * @psalm-suppress UndefinedDocblockClass
             */
            $user = $this->tokenStorage->getToken()->getUser();
            $this->profileManager->createHash($user, true, $this->isAuth);
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
}
