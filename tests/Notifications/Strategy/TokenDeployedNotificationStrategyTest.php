<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Image;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\TokenDeployedNotificationStrategy;
use App\Utils\NotificationChannels;
use PHPUnit\Framework\TestCase;

class TokenDeployedNotificationStrategyTest extends TestCase
{
    private const TYPE = 'TEST';
    private const TOKEN_NAME = 'TOKEN_TEST';
    private const TOKEN_AVATAR = '/media/default_token.png';
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

    private function mockMailer(bool $isAvailable, string $channel): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($isAvailable && NotificationChannels::EMAIL === $channel ?
            $this->once() :
            $this->never())
            ->method('sendTokenDeployedMail')
            ->with($this->user, self::TOKEN_NAME);

        return $mailer;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('getName')->willReturn(self::TOKEN_NAME);
        $token->expects($this->once())->method('getImage')->willReturn($this->mockTokenImage());

        return $token;
    }

    private function mockTokenImage(): Image
    {
        $image = $this->createMock(Image::class);
        $image->method('getUrl')
            ->willReturn(self::TOKEN_AVATAR);

        return $image;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function createNotification(bool $isAvailable, string $channel): TokenDeployedNotificationStrategy
    {
        $token = $this->mockToken();
        $extraData = ['tokenName' => self::TOKEN_NAME,'tokenAvatar' => self::TOKEN_AVATAR];

        return new TokenDeployedNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, [$this->user, self::TYPE, $channel], $extraData),
            $this->mockMailer($isAvailable, $channel),
            $token,
            self::TYPE,
        );
    }
}
