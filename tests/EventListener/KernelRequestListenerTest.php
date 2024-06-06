<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\KernelRequestListener;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class KernelRequestListenerTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testOnKernelRequest(
        bool $userLoggedIn,
        bool $isMasterRequest,
        string $userSessionId,
        bool $isSuccessful,
        string $route = '',
        bool $isBlackListed = false
    ): void {
        $listener = new KernelRequestListener(
            $this->mockTokenStorage($isSuccessful, $userSessionId),
            $this->mockAuthorizationChecker($userLoggedIn),
            $this->mockSession($isSuccessful, $userSessionId),
            $this->mockRouter($isSuccessful, $isBlackListed),
            $this->mockUserManager(),
            $this->mockBlacklistIpManager($isBlackListed)
        );
        $listener->onKernelRequest($this->mockRequestEvent($isSuccessful, $isMasterRequest, $isBlackListed, $route));
    }

    public function getTestCases(): array
    {
        return [
            "Success" => [
                "userLoggedIn" => true,
                "isMasterRequest" => true,
                "userSessionId" => "1",
                "isSuccessful" => true ,
            ],
            "Doesn't Proceed if route is blacklist_ip" => [
                "userLoggedIn" => true,
                "isMasterRequest" => true,
                "userSessionId" => "1",
                "isSuccessful" => false,
                "route" => "blacklist_ip",
            ],
            "Doesn't proceed if ip is blacklisted" => [
                "userLoggedIn" => true,
                "isMasterRequest" => true,
                "userSessionId" => "1",
                "isSuccessful" => false,
                "route" => "",
                "isBlackListed" => true,
            ],
            "Doesn't Proceed if not master request" => [
                "userLoggedIn" => false,
                "isMasterRequest" => false,
                "userSessionId" => "1",
                "isSuccessful" => false ,
            ],
            "Doesn't Proceed if user is not logged in" => [
                "userLoggedIn" => false,
                "isMasterRequest" => true,
                "userSessionId" => "1",
                "isSuccessful" => false ,
            ],
            "Doesn't Proceed if user is logged in but has no session id" => [
                "userLoggedIn" => true,
                "isMasterRequest" => true,
                "userSessionId" => "",
                "isSuccessful" => false ,
            ],
        ];
    }

    private function mockTokenStorage(bool $successful, ?string $userSessionId): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->method('getToken')
            ->willReturn($this->mockToken($userSessionId));

        $tokenStorage->expects($successful ? $this->once() : $this->never())
            ->method('setToken');

        return $tokenStorage;
    }
    private function mockAuthorizationChecker(bool $isLoggedIn): AuthorizationCheckerInterface
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $authorizationChecker->method('isGranted')
            ->will($this->onConsecutiveCalls($isLoggedIn, false));

        return $authorizationChecker;
    }

    private function mockSession(bool $successful, ?string $sessionId): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);

        $session->expects($successful ? $this->once() : $this->never())
            ->method('invalidate');

        $session->expects($successful ? $this->once() : $this->never())
            ->method('getName')
            ->willReturn('test');
        $session->expects($successful ? $this->once() : $this->never())
            ->method('get')
            ->willReturn('test');
        $session->expects($successful || !$sessionId ? $this->once() : $this->never())
            ->method('getId')
            ->willReturn($sessionId);

        return $session;
    }

    private function mockRouter(bool $successful, bool $isBlackListed): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);

        $router->expects($successful || $isBlackListed  ? $this->once() : $this->never())
            ->method('generate')
            ->willReturn('/test');

        return $router;
    }

    private function mockRequestEvent(
        bool $successful,
        bool $isMasterRequest,
        bool $isBlackListed,
        string $route = ''
    ): RequestEvent {
        $requestEvent = $this->createMock(RequestEvent::class);

        $requestEvent->expects($successful ? $this->exactly(2) : $this->any())
            ->method('getRequest')
            ->willReturn($this->mockRequest($route));

        $requestEvent->expects('blacklist_ip' === $route || $isBlackListed ? $this->never() : $this->once())
            ->method('isMasterRequest')
            ->willReturn($isMasterRequest);

        $requestEvent->expects($successful || $isBlackListed ? $this->once() : $this->never())
            ->method('setResponse');

        return $requestEvent;
    }

    private function mockToken(?string $userSessionId): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);

        $token->method('getUser')
            ->willReturn($this->mockUser($userSessionId));

        return $token;
    }

    private function mockUser(?string $userSessionId): User
    {
        $user = $this->createMock(User::class);

        $user->method('getSessionId')
            ->willReturn($userSessionId);
        $user->method('getRoles')
            ->willReturn([]);

        return $user;
    }

    private function mockUserManager(): UserManagerInterface
    {
        return $this->createMock(UserManagerInterface::class);
    }

    private function mockBlacklistIpManager(bool $isBlackListed): BlacklistIpManagerInterface
    {
        $blacklistIpManager = $this->createMock(BlacklistIpManagerInterface::class);

        $blacklistIpManager->method('isBlacklistedIp')
            ->willReturn($isBlackListed);

        return $blacklistIpManager;
    }

    private function mockRequest(string $route): Request
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_route' => $route]);

        $request->method('getClientIp')->willReturn('TEST');

        $request->method('getRequestUri')->willReturn('TEST');

        return $request;
    }
}
