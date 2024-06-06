<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\TokenMarketingTipsNotificationStrategy;
use App\Utils\NotificationChannels;
use PHPUnit\Framework\TestCase;

class TokenMarketingTipsNotificationStrategyTest extends TestCase
{
    private const TYPE = 'TEST';
    private const KB_LINK = 'https://test.com';
    private User $user;
    private string $timeInterval;
    private array $kbLinks;
    private array $allIntervals;


    protected function setUp(): void
    {
        $this->user = $this->mockUser();
        $this->timeInterval = 'test';
        $this->kbLinks = [self::KB_LINK];
        $this->allIntervals = ['test', 'test2'];
    }

    public function testSendNotificationWhenItsAvailableThroughWebsite(): void
    {
        $channel = NotificationChannels::WEBSITE;
        $notificationStrategy = $this->createNotification(true, $channel);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWhenItsNotAvailableThroughWebsite(): void
    {
        $channel = NotificationChannels::WEBSITE;
        $notificationStrategy = $this->createNotification(false, $channel);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWhenItsAvailableThroughEmail(): void
    {
        $channel = NotificationChannels::EMAIL;
        $notificationStrategy = $this->createNotification(true, $channel);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWhenItsNotAvailableThroughEmail(): void
    {
        $channel = NotificationChannels::EMAIL;
        $notificationStrategy = $this->createNotification(false, $channel);

        $notificationStrategy->sendNotification($this->user);
    }

    private function mockUserNotificationManager(
        bool $isAvailable,
        array $data,
        array $extraData = []
    ): UserNotificationManagerInterface {
        [$user, $type, $channel] = $data;

        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);

        $notificationManager->method('isNotificationAvailable')
            ->willReturnCallback(
                function ($user, $calledType, $calledChannel) use ($isAvailable, $channel) {
                    return $isAvailable && $calledChannel === $channel;
                }
            );

        $notificationManager->expects(
            $isAvailable && NotificationChannels::WEBSITE === $channel ?
                $this->once() :
                $this->never()
        )
            ->method('createNotification')
            ->with($user, $type, (array)json_encode($extraData));

        return $notificationManager;
    }

    private function mockMailer(bool $isAvailable, string $channel): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($isAvailable && NotificationChannels::EMAIL === $channel ?
            $this->once() :
            $this->never())
            ->method('sendTokenMarketingTipMail')
            ->with($this->user, self::KB_LINK);

        return $mailer;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function createNotification(bool $isAvailable, string $channel): TokenMarketingTipsNotificationStrategy
    {
        $extraData = ['kbLink' => self::KB_LINK];

        return new TokenMarketingTipsNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, [$this->user, self::TYPE, $channel], $extraData),
            $this->mockMailer($isAvailable, $channel),
            self::TYPE,
            $this->timeInterval,
            $this->kbLinks,
            $this->allIntervals
        );
    }
}
