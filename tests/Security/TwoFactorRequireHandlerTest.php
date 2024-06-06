<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Security\Request\RefererRequestHandlerInterface;
use App\Security\TwoFactorRequireHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class TwoFactorRequireHandlerTest extends TestCase
{
    public function testOnAuthenticationRequiredWithIs2faFalseAndIsRefererIsTrue(): void
    {
        $request = $this->mockRequest(true);
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils(),
            $this->mockTokenStorage(true),
            $this->mockRouter(false),
            $this->mockUrlGenerator(),
            $this->mockRefererRequestHandler(true),
        );

        $response = $handler->onAuthenticationRequired($request, $this->mockToken());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testOnAuthenticationRequiredWithIs2faTrueAndIsRefererIsTrue(): void
    {
        $request = $this->mockRequest(true);
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils(),
            $this->mockTokenStorage(true),
            $this->mockRouter(true),
            $this->mockUrlGenerator(),
            $this->mockRefererRequestHandler(true),
        );

        $response = $handler->onAuthenticationRequired($request, $this->mockToken());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testOnAuthenticationRequiredWithIs2faTrueAndIsRefererIsFalse(): void
    {
        $request = $this->mockRequest(false);
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils(),
            $this->mockTokenStorage(false),
            $this->mockRouter(true),
            $this->mockUrlGenerator(true),
            $this->mockRefererRequestHandler(false),
        );

        $response = $handler->onAuthenticationRequired($request, $this->mockToken());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testOnAuthenticationRequiredWithIs2faFalseAndIsRefererIsFalse(): void
    {
        $request = $this->mockRequest(false);
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils(),
            $this->mockTokenStorage(false),
            $this->mockRouter(false),
            $this->mockUrlGenerator(true),
            $this->mockRefererRequestHandler(false),
        );

        $response = $handler->onAuthenticationRequired($request, $this->mockToken());

        $this->assertInstanceOf(Response::class, $response);
    }

    private function mockRequest(bool $willLogout): Request
    {
        $request = $this->createMock(Request::class);

        $request->expects($this->once())->method('get')->with('_route')->willReturn('TEST');
        $request->expects($this->once())->method('getPathInfo')->willReturn('TEST');
        $request->expects($willLogout ? $this->once() : $this->never())
            ->method('getSession')
            ->willReturn($this->mockSession());

        return $request;
    }

    private function mockHttpUtils(): HttpUtils
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('generateUri')->willReturn('TEST');
        $httpUtils->method('createRedirectResponse')->willReturn($this->mockResponse());

        return $httpUtils;
    }

    private function mockUrlGenerator(bool $willGenerate2faUrl = false): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($willGenerate2faUrl ? $this->once() : $this->never())
            ->method('generate')
            ->willReturn('/2fa');

        return $urlGenerator;
    }

    private function mockResponse(): Response
    {
        return $this->createMock(Response::class);
    }

    private function mockRouter(bool $is2faProgress): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('match')->willReturn($is2faProgress ? [] : null);

        return $router;
    }

    private function mockSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function mockRefererRequestHandler(bool $isRefererValid): RefererRequestHandlerInterface
    {
        $handler = $this->createMock(RefererRequestHandlerInterface::class);
        $handler->expects($this->once())->method('isRefererValid')->willReturn($isRefererValid);

        return $handler;
    }

    private function mockToken(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function mockTokenStorage(bool $willLogout): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($willLogout ? $this->once() : $this->never())->method('setToken');

        return $tokenStorage;
    }
}
