<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\RegistrationCompletedListener;
use App\Logger\UserActionLogger;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class RegistrationCompletedListenerTest extends TestCase
{
    public function testOnFosuserRegistrationCompleted(): void
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('findByReferralCode')->willReturn($this->createMock(User::class));
        $um->expects($this->once())->method('updateUser');

        $listener = new RegistrationCompletedListener(
            $um,
            $this->createMock(UserActionLogger::class)
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $event->method('getUser')->willReturn($this->createMock(User::class));
        $event->method('getRequest')->willReturn($request);

        $listener->onFosuserRegistrationCompleted($event);
    }

    public function testOnFosuserRegistrationCompletedWithoutReferencer(): void
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('findByReferralCode')->willReturn(null);
        $um->expects($this->never())->method('updateUser');

        $listener = new RegistrationCompletedListener(
            $um,
            $this->createMock(UserActionLogger::class)
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $event->method('getUser')->willReturn($this->createMock(User::class));
        $event->method('getRequest')->willReturn($request);

        $listener->onFosuserRegistrationCompleted($event);
    }
}
