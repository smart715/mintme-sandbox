<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\AirdropEvent;
use App\Events\TokenEvents;
use App\EventSubscriber\AirdropCreatedSubscriber;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Utils\NotificationTypes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AirdropCreatedSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private User $user;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->user = $this->mockUser();
    }

    public function testSendMailForAirdropClaimed(): void
    {
        $subscriber = new AirdropCreatedSubscriber(
            $this->mockScheduledNotificationManager(NotificationTypes::MARKETING_AIRDROP_FEATURE, $this->user)
        );
        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockAirdropEvent($this->user);

        $this->dispatcher->dispatch($event, TokenEvents::AIRDROP_CREATED);
    }


    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockToken(User $user): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('getOwner')->willReturn($user);

        return $token;
    }



    private function mockScheduledNotificationManager(string $type, User $user): ScheduledNotificationManagerInterface
    {
        $scheduledNotificationManager = $this->createMock(ScheduledNotificationManagerInterface::class);
        $scheduledNotificationManager->expects($this->once())
            ->method('removeByTypeForUser')
            ->with($type, $user);

        return $scheduledNotificationManager;
    }

    private function mockAirdropEvent(User $user): AirdropEvent
    {
        $airdropEvent = $this->createMock(AirdropEvent::class);
        $airdropEvent->expects($this->once())
            ->method('getToken')
            ->willReturn($this->mockToken($user));

        return $airdropEvent;
    }
}
