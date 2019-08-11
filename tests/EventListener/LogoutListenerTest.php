<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;Bag;
use Symfony\Component\HttpFoundation\Request;

class LogoutListenerTest extends TestCase
{
    public function testOnLogout(): void

    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $logger = $this->createMock(UserActionLogger::class);
        $session = $this->createMock(SessionInterface::class);

        $checker->method('isGranted')->willReturn(false); 

        $listener = new LogoutListener(
            $logger,
            $checker,
            $session
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $event->method('getToken')->willReturn($this->createMock(TokenInterface::class));
        $event->method('getRequest')->willReturn($request);
        $event->method('getResponse')->willReturn($response);
        $listener->logout($event);
    }
}
