<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Image;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\User;
use App\Events\ConnectCompletedEvent;
use App\Events\DeployCompletedEvent;
use App\Events\TokenEvents;
use App\EventSubscriber\DeploySubscriber;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use phpDocumentor\Reflection\Types\Boolean;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DeploySubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider sendDeployCompletedMailNameProvider */
    public function testSuccessSendDeployCompletedMail(string $eventName): void
    {
        $subscriber = new DeploySubscriber(
            $this->mockMailer(),
            $this->mockLogger(),
            $this->mockUserNotificationManager(),
            $this->mockUserTokenFollowManager(true),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $tokenDeploy = $this->mockTokenDeploy();
        $token = $this->mockToken(true);
        $event = new DeployCompletedEvent($token, $tokenDeploy);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider sendDeployCompletedMailNameProvider */
    public function testFailureSendDeployCompletedMail(string $eventName): void
    {
        $subscriber = new DeploySubscriber(
            $this->mockMailer(false),
            $this->mockLogger(false),
            $this->mockUserNotificationManager(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $tokenDeploy = $this->mockTokenDeploy();
        $token = $this->mockToken();
        $event = new DeployCompletedEvent($token, $tokenDeploy);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function sendDeployCompletedMailNameProvider(): array
    {
        return [
            'token.deployed event' => [TokenEvents::DEPLOYED],
        ];
    }

    /** @dataProvider sendConnectCompletedMailProvider */
    public function testSuccessSendConnectCompletedMail(string $eventName): void
    {
        $subscriber = new DeploySubscriber(
            $this->mockMailer(),
            $this->mockLogger(),
            $this->mockUserNotificationManager(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $tokenDeploy = $this->mockTokenDeploy();
        $token = $this->mockToken();
        $event = new ConnectCompletedEvent($token, $tokenDeploy);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider sendConnectCompletedMailProvider */
    public function testFailureSendConnectCompletedMail(string $eventName): void
    {
        $subscriber = new DeploySubscriber(
            $this->mockMailer(false),
            $this->mockLogger(false),
            $this->mockUserNotificationManager(),
            $this->mockUserTokenFollowManager(),
        );

        $this->dispatcher->addSubscriber($subscriber);

        $tokenDeploy = $this->mockTokenDeploy();
        $token = $this->mockToken();
        $event = new ConnectCompletedEvent($token, $tokenDeploy);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function sendConnectCompletedMailProvider(): array
    {
        return [
            'token.connected event' => [TokenEvents::CONNECTED],
        ];
    }

    private function mockMailer(bool $success = true): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('checkConnection');

        if ($success) {
            $mailer->expects($this->once())
                ->method('sendOwnTokenDeployedMail');
        } else {
            $mailer->expects($this->once())
                ->method('sendOwnTokenDeployedMail')
                ->willThrowException(new \Exception());
        }

        return $mailer;
    }

    private function mockLogger(bool $success = true): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($success ? $this->never() : $this->once())->method('error');

        return $logger;
    }

    private function mockToken(bool $getImage = false): Token
    {
        $token = $this->createMock(Token::class);

        $token->expects($this->once())
            ->method('getProfile')
            ->willReturn($this->mockProfile());

        if ($getImage) {
            $image = $this->createMock(Image::class);
            $image->expects($this->once())
                ->method('getUrl')
                ->willReturn('tesURL');

            $token->expects($this->once())
                ->method('getImage')
                ->willReturn($image);
        }

        return $token;
    }

    private function mockTokenDeploy(): TokenDeploy
    {
        return $this->createMock(TokenDeploy::class);
    }

    private function mockProfile(): Profile
    {
        $profile = $this->createMock(Profile::class);

        $profile->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUser());

        return $profile;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockUserNotificationManager(): UserNotificationManagerInterface
    {
        return $this->createMock(UserNotificationManagerInterface::class);
    }

    private function mockUserTokenFollowManager(bool $followers = false): UserTokenFollowManagerInterface
    {
        $userTokenFollowManager = $this->createMock(UserTokenFollowManagerInterface::class);

        if ($followers) {
            $userTokenFollowManager->expects($this->once())
                ->method('getFollowers')
                ->willReturn([$this->mockUser()]);
        }

        return $userTokenFollowManager;
    }
}
