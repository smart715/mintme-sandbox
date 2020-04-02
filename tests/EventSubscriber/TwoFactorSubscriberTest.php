<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\TwoFactorSubscriber;
use App\Manager\TwoFactorManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorSubscriberTest extends TestCase
{
    public function testSuccessRequired(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required')
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccessOptional(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('optional')
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccessOff(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true),
            $this->mockTwoFactorManager(true),
            $this->mockRouter(false)
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccsess2FAIsNotEnabledOptional(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true, false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('optional')
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccessWithoutRoute(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required', false)
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccessWithout2FAOption(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required', true, false)
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testSuccessWithOffOption(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true),
            $this->mockTwoFactorManager(true),
            $this->mockRouter(false)
        );

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testFailedInvalidOptionValue(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('invalidOptionValue')
        );

        $this->expectException(InvalidArgumentException::class);

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testFailedUserRequired(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required')
        );

        $this->expectExceptionMessage("Invalid user");

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testFailed2FAIsNotEnabledRequired(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true, false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required')
        );

        $this->expectExceptionMessage("2FA is not enabled");

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testFailedWithoutUserToken(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(false, true, false),
            $this->mockTwoFactorManager(true),
            $this->mockRouter('required')
        );

        $this->expectExceptionMessage("Invalid user");

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    public function testFailed2FAInvaidRequired(): void
    {
        $subscriber = new TwoFactorSubscriber(
            $this->mockTokenStorage(true),
            $this->mockTwoFactorManager(false),
            $this->mockRouter('required')
        );

        $this->expectExceptionMessage("Invalid 2FA code");

        $subscriber->onRequest(
            $this->mockGetResponseEvent("fooCode")
        );
    }

    private function mockTokenStorage(
        bool $hasUser,
        bool $enableTwoFactor = true,
        bool $hasToken = true
    ): TokenStorageInterface {
        $storage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);

        $user->method('isGoogleAuthenticatorEnabled')->willReturn($enableTwoFactor);
        $token->method('getUser')->willReturn($hasUser ? $user : null);
        $storage->method('getToken')->willReturn($hasToken ? $token : null);

        return $storage;
    }

    private function mockTwoFactorManager(bool $validate): TwoFactorManagerInterface
    {
        $manager = $this->createMock(TwoFactorManagerInterface::class);
        $manager->method('checkCode')->willReturn($validate);

        return $manager;
    }

    /** @param string|bool $twoFa */
    private function mockRouter($twoFa, bool $hasRoute = true, bool $hasOptions = true): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);
        $collection = $this->createMock(RouteCollection::class);
        $route = $this->createMock(Route::class);

        $route->method('getOptions')->willReturn($hasOptions ? [
            '2fa' => $twoFa,
        ] : []);
        $collection->method('get')->willReturn($hasRoute ? $route : null);
        $router->expects($this->once())->method('getRouteCollection')->willReturn($collection);

        return $router;
    }

    private function mockGetResponseEvent(string $code): GetResponseEvent
    {
        $event = $this->createMock(GetResponseEvent::class);
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturn($code);
        $request->attributes = $this->createMock(ParameterBag::class);

        $event->method('getRequest')->willReturn($request);

        return $event;
    }
}
