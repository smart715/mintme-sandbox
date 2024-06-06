<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NewInvestorNotificationStrategy;
use App\Utils\NotificationChannels;
use PHPUnit\Framework\TestCase;

class NewInvestorNotificationStrategyTest extends TestCase
{
    private const TYPE = 'TEST';
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->mockUser();
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

    private function mockMailer(bool $isAvailable, string $channel, array $data): MailerInterface
    {
        [$token, $extraData] = $data;
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($isAvailable && NotificationChannels::EMAIL === $channel ?
            $this->once() :
            $this->never())
            ->method('sendNewInvestorMail')
            ->with($token, $extraData['profile']);

        return $mailer;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function createNotification(bool $isAvailable, string $channel): NewInvestorNotificationStrategy
    {
        $token = $this->mockToken();
        $extraData = ['profile' => 'TEST'];

        return new NewInvestorNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, [$this->user, self::TYPE, $channel], $extraData),
            $this->mockMailer($isAvailable, $channel, [$token, $extraData]),
            $token,
            self::TYPE,
            $extraData
        );
    }
}
