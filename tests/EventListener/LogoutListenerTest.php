<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\LogoutListener;
use App\Logger\UserActionLogger;
use App\Mercure\Authorization as MercureAuthorization;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LogoutListenerTest extends TestCase
{
    public function testOnLogoutWithAuthRemembered(): void
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $logger = $this->createMock(UserActionLogger::class);
        $session = $this->createMock(SessionInterface::class);
        $route = $this->createMock(UrlGeneratorInterface::class);
        $mercureAuth = $this->createMock(MercureAuthorization::class);

        $checker->method('isGranted')->with('IS_AUTHENTICATED_REMEMBERED')->willReturn(true);

        $listener = new LogoutListener(
            $logger,
            $checker,
            $session,
            $route,
            $mercureAuth
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = $this->createMock(TokenInterface::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $hb = $this->createMock(HeaderBag::class);
        $hb->method('get')->willReturn(false);

        $request->headers = $hb;

        $event->method('getUser')->willReturn($token);
        $event->method('getRequest')->willReturn($request);

        $checker->expects($this->once())->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->willReturn(false);

        $session->expects($this->once())->method('set');

        $listener->logout($request, $response, $token);
    }

    public function testOnLogoutWithoutAuthRemembered(): void
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $logger = $this->createMock(UserActionLogger::class);
        $session = $this->createMock(SessionInterface::class);
        $route = $this->createMock(UrlGeneratorInterface::class);
        $mercureAuth = $this->createMock(MercureAuthorization::class);

        $checker->method('isGranted')->with('IS_AUTHENTICATED_REMEMBERED')->willReturn(false);

        $listener = new LogoutListener(
            $logger,
            $checker,
            $session,
            $route,
            $mercureAuth
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = $this->createMock(TokenInterface::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $hb = $this->createMock(HeaderBag::class);
        $hb->method('get')->willReturn(false);

        $request->headers = $hb;

        $event->method('getUser')->willReturn($token);
        $event->method('getRequest')->willReturn($request);

        $checker->expects($this->once())->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->willReturn(true);

        $session->expects($this->never())->method('set');

        $listener->logout($request, $response, $token);
    }
}
