<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\RegistrationCompletedListener;
use App\Logger\UserActionLogger;
use App\Manager\UserManagerInterface;
use App\Utils\Facebook\FacebookPixelCommunicator;
use App\Utils\Facebook\FacebookPixelCommunicatorInterface;
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
            $this->createMock(UserActionLogger::class),
            $this->mockFacebookPixeCommunicator()
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('');
        $event->method('getUser')->willReturn($user);
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
            $this->createMock(UserActionLogger::class),
            $this->mockFacebookPixeCommunicator()
        );

        $event = $this->createMock(FilterUserResponseEvent::class);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
    
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('');
        $event->method('getUser')->willReturn($user);
        $event->method('getRequest')->willReturn($request);

        $listener->onFosuserRegistrationCompleted($event);
    }
    
    private function mockFacebookPixeCommunicator(): FacebookPixelCommunicatorInterface
    {
        $mock = $this->createMock(FacebookPixelCommunicatorInterface::class);
        $mock->method('sendUserEvent')->willReturn(null);
        
        return $mock;
    }
}
