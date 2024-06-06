<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use App\Mercure\Authorization as MercureAuthorization;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

/** @codeCoverageIgnore */
class LogoutListener implements LogoutHandlerInterface
{
    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var SessionInterface */
    private $session;

    private UrlGeneratorInterface $route;

    private MercureAuthorization $mercureAuthorization;

    public function __construct(
        UserActionLogger $userActionLogger,
        AuthorizationCheckerInterface $authorizationChecker,
        SessionInterface $session,
        UrlGeneratorInterface $route,
        MercureAuthorization $mercureAuthorization
    ) {
        $this->userActionLogger     = $userActionLogger;
        $this->authorizationChecker = $authorizationChecker;
        $this->session              = $session;
        $this->route                = $route;
        $this->mercureAuthorization = $mercureAuthorization;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        do {
            if ($exception instanceof LogoutException) {
                $this->handleLogoutException($event, $exception);

                return;
            }
        } while (null !== $exception = $exception->getPrevious());
    }

    private function handleLogoutException(ExceptionEvent $event, LogoutException $exception): void
    {
        if ('Invalid CSRF token.' !== $exception->getMessage()) {
            return;
        }

        try {
            $homePage = $this->route->generate('homepage');
            $event->setResponse(new RedirectResponse($homePage));
            $event->allowCustomResponseCode();
        } catch (\Throwable $exception) {
            $event->setThrowable($exception);
        }
    }

    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        $this->userActionLogger->info('Logout');

        $this->mercureAuthorization->clearCookie($request, 'public');

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->session->set('has_authenticated', true);
        }

        $refers = $request->headers->get('Referer');

        if ($refers) {
            $this->session->set('logout_referer', $refers);
        }
    }
}
