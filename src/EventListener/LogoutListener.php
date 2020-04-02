<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
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

    public function __construct(UserActionLogger $userActionLogger, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session)
    {
        $this->userActionLogger = $userActionLogger;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
    }

    /** @inheritDoc */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->userActionLogger->info('Logout');
        
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->session->set('has_authenticated', true);
        }
    }
}
