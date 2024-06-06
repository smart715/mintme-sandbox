<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Events\UserChangeEvents;
use App\EventSubscriber\ResettingSubscriber;
use App\Mailer\MailerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ResettingSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider sendPasswordResetMailEventNameProvider */
    public function testSendPasswordResetMail(string $eventName): void
    {
        $subscriber = new ResettingSubscriber(
            $this->mockMailer($this->once()),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockFilterUserResponseEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }


    public function sendPasswordResetMailEventNameProvider(): array
    {
        return [
            'fos_user.resetting.reset.completed event' => [FOSUserEvents::RESETTING_RESET_COMPLETED],
            'toasted.success.password_updated event' => [UserChangeEvents::PASSWORD_UPDATED_MSG],
        ];
    }


    /** @dataProvider resetTokenEventNameProvider */
    public function testResetToken(string $eventName): void
    {
        $subscriber = new ResettingSubscriber(
            $this->mockMailer($this->never()),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockGetResponseUserEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function resetTokenEventNameProvider(): array
    {
        return [
            'fos_user.resetting.reset.request event' => [FOSUserEvents::RESETTING_RESET_REQUEST],
        ];
    }


    private function mockMailer(InvokedCount $count): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($count)->method('sendPasswordResetMail');

        return $mailer;
    }

    private function mockFilterUserResponseEvent(): FilterUserResponseEvent
    {
        $event = $this->createMock(FilterUserResponseEvent::class);
        $event->expects($this->once())->method('getUser')->willReturn($this->mockUser());

        return $event;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockGetResponseUserEvent(): GetResponseUserEvent
    {
        $event = $this->createMock(GetResponseUserEvent::class);
        $event->expects($this->once())->method('getUser')->willReturn($this->mockUser());

        return $event;
    }
}
