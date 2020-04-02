<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\RegistrationInitializedListener;
use FOS\UserBundle\Event\GetResponseUserEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RegistrationInitializedListenerTest extends TestCase
{
    public function testOnFosuserRegistrationInitialize(): void
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(true);

        $event = $this->createMock(GetResponseUserEvent::class);
        $event->expects($this->once())->method('setResponse');

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->once())->method('generate')->willReturn('foo');

        $listener = new RegistrationInitializedListener($router, $checker);

        $listener->onFosuserRegistrationInitialize($event);
    }

    public function testOnFosuserRegistrationInitializeIsNotGranted(): void
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(false);

        $event = $this->createMock(GetResponseUserEvent::class);
        $event->expects($this->never())->method('setResponse');

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->never())->method('generate')->willReturn('foo');

        $listener = new RegistrationInitializedListener($router, $checker);

        $listener->onFosuserRegistrationInitialize($event);
    }
}
