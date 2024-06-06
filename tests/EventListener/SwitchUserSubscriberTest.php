<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\SwitchUserSubscriber;
use App\Logger\UserActionLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserSubscriberTest extends TestCase
{
    /** @dataProvider  boolProvider */
    public function testSwitchUserSubscriber(?bool $val): void
    {
        $subscriber = new SwitchUserSubscriber($this->mockUserActionLogger($val));
        $subscriber->onSwitchUser($this->mockSwitchUserEvent($val));
    }

    public function boolProvider(): array
    {
        return [[false], [true], [null]];
    }

    private function mockSwitchUserEvent(?bool $val): SwitchUserEvent
    {
        $event = $this->createMock(SwitchUserEvent::class);
        $event->method('getRequest')->willReturn($this->mockRequest($val));
        $event->method('getTargetUser')->willReturn($this->mockUser());

        return $event;
    }

    private function mockRequest(?bool $val): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getSession')->willReturn($this->mockSession($val));

        return $request;
    }

    private function mockSession(?bool $val): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn($val);
        $session->expects($this->once())->method('set')
        ->with('view_only_mode', !$val);

        return $session;
    }

    private function mockUserActionLogger(?bool $val): UserActionLogger
    {
        $logger = $this->createMock(UserActionLogger::class);
        $logger->expects($this->once())->method('info')
        ->with($val ? 'Leave viewonly mode, switch back to ' : 'Enter viewonly mode, log in as ');

        return $logger;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
