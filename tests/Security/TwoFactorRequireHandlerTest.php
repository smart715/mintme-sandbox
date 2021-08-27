<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Security\Request\RefererRequestHandlerInterface;
use App\Security\TwoFactorRequireHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class TwoFactorRequireHandlerTest extends TestCase
{
    /** @var RouteCollection */
    private $routeCollection;

    public function setUp(): void
    {
        $this->routeCollection = new RouteCollection();
        $this->routeCollection->add('en__RG__secure', new Route('secure', [], [], ['2fa_progress' => true]));
        $this->routeCollection->add('en__RG__free', new Route('free', [], [], ['2fa_progress' => false]));
    }

    public function testOnAuthenticationRequiredSecure(): void
    {
        $request = $this->mockRequest('secure');
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils($request),
            $this->createMock(TokenStorageInterface::class),
            $this->mockRouter(),
            $this->mockUrlGenerator(),
            $this->createMock(SessionInterface::class),
            $this->createMock(RefererRequestHandlerInterface::class)
        );
        $response = $handler->onAuthenticationRequired($request, $this->createMock(TokenInterface::class));
        $this->assertEquals('/2fa', $response->getContent());
    }

    public function testOnAuthenticationRequiredFree(): void
    {
        $request = $this->mockRequest('free');
        $handler = new TwoFactorRequireHandler(
            $this->mockHttpUtils($request),
            $this->createMock(TokenStorageInterface::class),
            $this->mockRouter(),
            $this->mockUrlGenerator(),
            $this->createMock(SessionInterface::class),
            $this->createMock(RefererRequestHandlerInterface::class)
        );
        $response = $handler->onAuthenticationRequired($request, $this->createMock(TokenInterface::class));
        $this->assertEquals('/free', $response->getContent());
    }

    /** @return MockObject|Request */
    private function mockRequest(string $name): Request
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('invalidate');

        $request = $this->createMock(Request::class);
        $request->method('getSession')->willReturn($session);
        $request->method('get')->with('_route')->willReturn($name);

        return $request;
    }

    /** @return MockObject|HttpUtils */
    private function mockHttpUtils(Request $request): HttpUtils
    {
        $name = $request->get('_route');
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('generateUri')->with($request, $name)->willReturn("/{$name}");
        $uri = 'secure' === $name
            ? TwoFactorFactory::DEFAULT_AUTH_FORM_PATH
            : $this->routeCollection->get('en__RG__free')->getPath();
        $httpUtils
            ->method('createRedirectResponse')
            ->with($this->mockRequest($name), $uri)
            ->willReturn($this->mockResponse($uri));

        return $httpUtils;
    }

    private function mockUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/2fa');

        return $urlGenerator;
    }

    /** @return MockObject|Response */
    private function mockResponse(string $name): Response
    {
        $response = $this->createMock(Response::class);
        $response->method('getContent')->willReturn($name);

        return $response;
    }

    /** @return MockObject|RouterInterface */
    private function mockRouter(): RouterInterface
    {
        $headerBag = $this->createMock(HeaderBag::class);
        $headerBag->method('get')->willReturn('referer');

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($this->routeCollection);
        $router->method('session')->willReturn($headerBag);

        return $router;
    }
}
