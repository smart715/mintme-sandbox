<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Image;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Events\RewardEvent;
use App\EventSubscriber\RewardEventSubscriber;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\UserTokenFollowManager;
use App\Repository\RewardRepository;
use App\Utils\Policy\NotificationPolicyInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RewardEventSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private const TOKEN_AVATAR = '/media/default_token.png';

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider onRewardNewEventNameProvider */
    public function testOnRewardNewWithBountyType(string $eventName): void
    {
        $followers = [$this->mockUser()];
        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, true);

        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(true),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager($token, $followers),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider onRewardNewEventNameProvider */
    public function testOnRewardNewWithNoUsersCanReceiveNotification(string $eventName): void
    {
        $followers = [$this->mockUser()];
        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, true);

        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(false),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager($token, $followers),
        );

        $this->dispatcher->addSubscriber($subscriber);



        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider onRewardNewEventNameProvider */
    public function testOnRewardNewWithNotBountyType(string $eventName): void
    {
        $followers = [$this->mockUser()];
        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, false);

        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(false),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager($token, $followers),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onRewardNewEventNameProvider(): array
    {
        return [
            'reward.new event' => [RewardEvent::REWARD_NEW],
        ];
    }


    /** @dataProvider onParticipantAddedEventNameProvider */
    public function testOnParticipantAdded(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $owner = $this->mockUser($this->never());
        $token = $this->mockToken($owner);
        $event = $this->mockRewardEvent($token);

        $this->dispatcher->dispatch($event, $eventName);
    }


    public function onParticipantAddedEventNameProvider(): array
    {
        return [
            'participant.added event' => [RewardEvent::PARTICIPANT_ADDED ],
        ];
    }


    /** @dataProvider onVolunteerNewEventNameProvider */
    public function testOnVolunteerNew(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $owner = $this->mockUser($this->never());
        $token = $this->mockToken($owner);
        $event = $this->mockRewardEvent($token);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onVolunteerNewEventNameProvider(): array
    {
        return [
            'volunteer.new event' => [RewardEvent::VOLUNTEER_NEW],
        ];
    }

    /** @dataProvider onVolunteerAcceptedEventNameProvider */
    public function testOnVolunteerAccepted(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once());

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onVolunteerAcceptedEventNameProvider(): array
    {
        return [
            'volunteer.accepted event' => [RewardEvent::VOLUNTEER_COMPLETED],
        ];
    }

    /** @dataProvider onVolunteerCompletedEventNameProvider */
    public function testOnVolunteerCompleted(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once());

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onVolunteerCompletedEventNameProvider(): array
    {
        return [
            'volunteer.accepted event' => [RewardEvent::VOLUNTEER_COMPLETED],
        ];
    }

    /** @dataProvider onVolunteerRejectedEventNameProvider */
    public function testOnVolunteerRejected(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once());

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onVolunteerRejectedEventNameProvider(): array
    {
        return [
            'volunteer.rejected event' => [RewardEvent::VOLUNTEER_REJECTED],
        ];
    }

    /** @dataProvider onRewardDeletedEventNameProvider */
    public function testOnRewardDeleted(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once(), null, true);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onRewardDeletedEventNameProvider(): array
    {
        return [
            'reward.deleted event' => [RewardEvent::REWARD_DELETED],
        ];
    }

    /** @dataProvider onParticipantRejectedEventNameProvider */
    public function testOnParticipantRejected(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once());

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onParticipantRejectedEventNameProvider(): array
    {
        return [
            'participant.rejected event' => [RewardEvent::PARTICIPANT_REJECTED],
        ];
    }

    /** @dataProvider onParticipantRefundEventNameProvider */
    public function testOnParticipantRefund(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $owner = $this->createMock(User::class);
        $owner->expects($this->once())->method('getNickname')->willReturn('test');

        $token = $this->mockToken($owner);
        $event = $this->mockRewardEvent($token, null, $this->once(), true);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onParticipantRefundEventNameProvider(): array
    {
        return [
            'participant.refunded event' => [RewardEvent::PARTICIPANT_REFUNDED],
        ];
    }

    /** @dataProvider onParticipantDeliveredEventNameProvider */
    public function testOnParticipantDelivered(string $eventName): void
    {
        $subscriber = new RewardEventSubscriber(
            $this->mockUserNotificationManager(),
            $this->mockMailer(),
            $this->mockNotificationPolicy(),
            $this->mockMoneyWrapper(),
            $this->mockRewardRepository(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockRewardEvent($token, null, $this->once());

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function onParticipantDeliveredEventNameProvider(): array
    {
        return [
            'participant.delivered event' => [RewardEvent::PARTICIPANT_DELIVERED],
        ];
    }

    private function mockRewardEvent(
        ?Token $token,
        ?bool $isBountyType = null,
        ?InvokedCount $getRewardMemberCount = null,
        ?bool $isRewardParticipant = null,
        ?bool $isDeleteReward = null
    ): RewardEvent {
        $event = $this->createMock(RewardEvent::class);
        $event->expects($this->once())
            ->method('getReward')
            ->willReturn($this->mockReward($token, $isBountyType));

        if ($getRewardMemberCount && !$isDeleteReward) {
            $event->expects($getRewardMemberCount)
                ->method('getRewardMember')
                ->willReturn($isRewardParticipant ? $this->mockRewardParticipant() : $this->mockRewardMember());
        }

        if ($isDeleteReward) {
            $event->expects($getRewardMemberCount)
                ->method('getRewardMembers')
                ->willReturn($this->mockRewardMembers());
        }

        return $event;
    }

    private function mockUserNotificationManager(): UserNotificationManagerInterface
    {
        return $this->createMock(UserNotificationManagerInterface::class);
    }

    private function mockMailer(): MailerInterface
    {
        return $this->createMock(MailerInterface::class);
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        return $this->createMock(MoneyWrapperInterface::class);
    }

    private function mockNotificationPolicy(?bool $canReceiveNotification = null): NotificationPolicyInterface
    {
        $notificationPolicy = $this->createMock(NotificationPolicyInterface::class);

        if (null !== $canReceiveNotification) {
            $notificationPolicy->method('canReceiveNotification')->willReturn($canReceiveNotification);
        }

        return $notificationPolicy;
    }

    private function mockReward(?Token $token, ?bool $isBountyType): Reward
    {
        $reward = $this->createMock(Reward::class);

        if ($token) {
            $reward->method('getToken')->willReturn($token);
        }

        if (null !== $isBountyType) {
            $reward->expects($this->once())->method('isBountyType')->willReturn($isBountyType);
        }

        return $reward;
    }

    private function mockUser(?InvokedCount $getIdCount = null): User
    {
        $user = $this->createMock(User::class);

        if ($getIdCount) {
            $user->expects($getIdCount)->method('getId')->willReturn(rand(1, 100000000));
        }

        return $user;
    }

    private function mockToken(?User $owner = null, ?array $users = null): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getImage')->willReturn($this->mockTokenImage());

        if ($owner) {
            $token->expects($this->once())->method('getOwner')->willReturn($owner);
        }

        if ($users) {
            $token->expects($this->once())->method('getUsers')->willReturn($users);
        }

        return $token;
    }

    private function mockTokenImage(): Image
    {
        $image = $this->createMock(Image::class);
        $image->method('getUrl')
            ->willReturn(self::TOKEN_AVATAR);

        return $image;
    }

    private function mockRewardMember(): RewardMemberInterface
    {
        $rewardMember = $this->createMock(RewardMemberInterface::class);
        $rewardMember->expects($this->once())->method('getUser')->willReturn($this->mockUser());

        return $rewardMember;
    }

    private function mockRewardMembers(): array
    {
        $rmi0 = $this->createMock(RewardMemberInterface::class);
        $rmi1 = $this->createMock(RewardMemberInterface::class);
        $rmi2 = $this->createMock(RewardMemberInterface::class);

        $rewardMembers = [$rmi0, $rmi1, $rmi2];

        foreach ($rewardMembers as $rewardMember) {
            $rewardMember->expects($this->once())->method('getUser')->willReturn($this->mockUser());
        }

        return $rewardMembers;
    }

    private function mockRewardParticipant(): RewardParticipant
    {
        $rewardParticipant = $this->createMock(RewardParticipant::class);
        $rewardParticipant
            ->expects($this->once())
            ->method('getFullPrice')
            ->willReturn(new Money(1, new Currency(Symbols::TOK)));

        return $rewardParticipant;
    }

    private function mockRewardRepository(): RewardRepository
    {
        return $this->createMock(RewardRepository::class);
    }

    private function mockUserTokenFollowManager(?Token $token = null, array $followers = []): UserTokenFollowManager
    {
        $userTokenFollowManager = $this->createMock(UserTokenFollowManager::class);

        if ($token) {
            $userTokenFollowManager
                ->expects($this->once())
                ->method('getFollowers')
                ->willReturn($followers);
        }

        return $userTokenFollowManager;
    }
}
