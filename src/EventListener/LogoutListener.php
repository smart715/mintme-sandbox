<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutListener implements LogoutHandlerInterface
{
    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(UserActionLogger $userActionLogger)
    {
        $this->userActionLogger = $userActionLogger;
    }

    /** @inheritDoc */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->userActionLogger->info('Logout');
    }
}
