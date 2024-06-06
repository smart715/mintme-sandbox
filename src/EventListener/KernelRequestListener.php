<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\Blacklist\BlacklistIp;
use App\Entity\User;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManager;
use App\Manager\UserManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class KernelRequestListener
{
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private SessionInterface $session;
    private RouterInterface $router;
    private BlacklistIpManagerInterface $blacklistIpManager;
    private UserManagerInterface $userManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        SessionInterface $session,
        RouterInterface $router,
        UserManagerInterface $userManager,
        BlacklistIpManagerInterface $blacklistIpManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->router = $router;
        $this->blacklistIpManager = $blacklistIpManager;
        $this->userManager = $userManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $route = $request->attributes->get('_route');

        if ('blacklist_ip' === $route) {
            return;
        }

        // TODO: hitting the database on every request is not ideal, Caching isn't reliable with load balancers
        // Memory based DBs would work, but that's a lot of overhead for a simple feature, a better solution is needed
        /** @var BlacklistIp|null $blacklistIp */
        $blacklistIp = $this->blacklistIpManager->getBlacklistIpByAddress($event->getRequest()->getClientIp());

        if ($this->blacklistIpManager->isBlacklistedIp($blacklistIp)) {
            $response = $this->generateResponse('blacklist_ip');
            $event->setResponse($response);

            return;
        }

        if (!$event->isMasterRequest() || !$this->isUserLoggedIn() || $this->isDevApiCall($request)) {
            return;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('auth', 'Invalid user');
        }

        $sessionId = $this->session->getId();

        if (($sessionId && $this->userManager->isSessionIdValid($user, $sessionId)) || !$user->getSessionId()) {
            return;
        }

        if (in_array('ROLE_PREVIOUS_ADMIN', $user->getRoles())
        || $this->authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN')) {
            return;
        }

        $this->tokenStorage->setToken(null);
        $this->session->invalidate();

        $cookieNames = [
            $this->session->getName(),
            $this->session->get('session.remember_me.name'),
        ];

        $response = $this->generateResponse('fos_user_security_logout');

        foreach ($cookieNames as $cookieName) {
            !$cookieName ?: $response->headers->clearCookie($cookieName);
        }

        $event->setResponse($response);
    }

    protected function isUserLoggedIn(): bool
    {
        try {
            return $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
        } catch (AuthenticationCredentialsNotFoundException $exception) {
            return false;
        }
    }

    private function generateResponse(string $routeName): RedirectResponse
    {
        $redirectUrl = $this->router->generate($routeName);

        return new RedirectResponse($redirectUrl);
    }

    private function isDevApiCall(Request $request): bool
    {
        return false !== strpos($request->getRequestUri(), '/dev/api/v');
    }
}
