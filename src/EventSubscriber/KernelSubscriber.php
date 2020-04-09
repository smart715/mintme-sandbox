<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(
        bool $isAuth,
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->isAuth = $isAuth;
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
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
        $csrf = $request->getRequest()->headers->get('X-CSRF-TOKEN', '');

        if (!is_string($csrf) ||
            ($request->getRequest()->isXmlHttpRequest() &&
            $this->isApiRequest($request->getRequest()) &&
            !$this->isCsrfTokenValid($csrf))
        ) {
            throw new AccessDeniedHttpException("Invalid token given");
        }

        if (is_object($this->tokenStorage->getToken()) &&
            ($this->tokenStorage->getToken()->getUser() instanceof User) &&
            !$request->getRequest()->isXmlHttpRequest()
        ) {
            /** @var User $user */
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
