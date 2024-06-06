<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Events\NewDeviceDetectedEvent;
use App\EventSubscriber\LoginInfoSubscriber;
use App\Mailer\MailerInterface;
use App\Manager\UserLoginInfoManager;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginInfoSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider updateLoginDeviceInfoEventNameProvider */
    public function testUpdateLoginDeviceInfo(string $eventName): void
    {
        $subscriber = new LoginInfoSubscriber(
            $this->mockMailer($this->never()),
            $this->mockUserLoginInfoManager($this->once()),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockInteractiveLoginEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }


    public function updateLoginDeviceInfoEventNameProvider(): array
    {
        return [
            'security.interactive_login event' => [SecurityEvents::INTERACTIVE_LOGIN],
        ];
    }


    /** @dataProvider sendNewDeviceDetectedMailEventNameProvider */
    public function testFailureHandleTokenUserEvent(string $eventName): void
    {
        $subscriber = new LoginInfoSubscriber(
            $this->mockMailer($this->once()),
            $this->mockUserLoginInfoManager($this->never()),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockNewDeviceDetectedEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function sendNewDeviceDetectedMailEventNameProvider(): array
    {
        return [
            'newdevice.detected event' => [NewDeviceDetectedEvent::NAME],
        ];
    }


    private function mockMailer(InvokedCount $count): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($count)->method('sendNewDeviceDetectedMail');

        return $mailer;
    }

    private function mockUserLoginInfoManager(InvokedCount $count): UserLoginInfoManager
    {
        $userLoginInfoManager = $this->createMock(UserLoginInfoManager::class);

        $userLoginInfoManager->expects($count)->method('updateUserDeviceLoginInfo');

        return $userLoginInfoManager;
    }

    private function mockInteractiveLoginEvent(): InteractiveLoginEvent
    {
        return $this->createMock(InteractiveLoginEvent::class);
    }

    private function mockNewDeviceDetectedEvent(): NewDeviceDetectedEvent
    {
        $event = $this->createMock(NewDeviceDetectedEvent::class);
        $event->expects($this->once())->method('getUser');
        $event->expects($this->once())->method('getUserDeviceLoginInfo');

        return $event;
    }
}
