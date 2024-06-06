<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Image;
use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\PostManager;
use App\Manager\PostManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\TokenPostNotificationStrategy;
use App\Utils\Policy\NotificationPolicyInterface;
use PHPUnit\Framework\TestCase;

class TokenPostNotificationStrategyTest extends TestCase
{
    private const TYPE = 'TEST';
    private const TOKEN_NAME = 'TOKEN_TEST';
    private const TOKEN_AVATAR = '/media/default_token.png';
    private const POST_COUNT = 10;
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->mockUser();
    }

    public function testSendNotificationSuccess(): void
    {
        $notificationStrategy = $this->createNotification(true, false);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWhenItsNotAvailable(): void
    {
        $notificationStrategy = $this->createNotification(false, true);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWhenItsAvailableButUserCantReceive(): void
    {
        $notificationStrategy = $this->createNotification(true, false);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWithQuietTokenWillNotProceed(): void
    {
        $notificationStrategy = $this->createNotification(true, true, true);

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWithOnePost(): void
    {
        $notificationStrategy = $this->createNotificationWithOnePost(
            true,
            true,
            false
        );

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWithManyPosts(): void
    {
        $notificationStrategy = $this->createNotificationWithManyPosts(
            true,
            true,
            false
        );

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWithOutPosts(): void
    {
        $notificationStrategy = $this->createNotificationWithoutPosts(
            true,
            true,
            false
        );

        $notificationStrategy->sendNotification($this->user);
    }

    private function mockUserNotificationManager(
        bool $isAvailable,
        bool $canReceiveNotification,
        bool $isQuiet
    ): UserNotificationManagerInterface {
        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);
        $notificationManager->method('isNotificationAvailable')->willReturn($isAvailable);
        $notificationManager->expects(
            $isAvailable && $canReceiveNotification && !$isQuiet ?
                $this->once() : $this->never()
        )->method('createNotification');

        return $notificationManager;
    }

    private function mockToken(bool $isQuiet): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('getName')->willReturn(self::TOKEN_NAME);
        $token->expects($this->once())->method('getImage')->willReturn($this->mockTokenImage());
        $token->expects($this->once())->method('isQuiet')->willReturn($isQuiet);

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

    private function mockMailerNoSent(): MailerInterface
    {
        $mail =  $this->createMock(MailerInterface::class);
        $mail->expects($this->never())->method('sendNewPostMail');

        return $mail;
    }

    private function mockMailerSent(): MailerInterface
    {
        $mail =  $this->createMock(MailerInterface::class);
        $mail->expects($this->once())->method('sendNewPostMail');

        return $mail;
    }

    private function mockPostManager(): PostManagerInterface
    {
        return $this->createMock(PostManagerInterface::class);
    }

    private function mockPostManagerWithOnePost(): PostManagerInterface
    {
        $postManager = $this->createMock(PostManagerInterface::class);

        $post = new Post();
        $post->setSlug('post slug');
        $post->setTitle('post title');
        $post->setContent('post content');

        $postManager->expects($this->once())->method('getPostsCreatedAtByToken')->willReturn([
            $post,
        ]);

        return $postManager;
    }

    private function mockPostManagerWithManyPosts(): PostManagerInterface
    {
        $posts = [];
        $postManager = $this->createMock(PostManager::class);

        for ($index = 0; $index < self::POST_COUNT; ++$index) {
            $post = new Post();
            $post->setToken($this->createMock(Token::class));
            $post->setSlug('post slug' . $index);
            $post->setTitle('post title' . $index);
            $post->setContent('post content' . $index);
            $posts[] = $post;
        }

        $postManager->expects($this->once())->method('getPostsCreatedAtByToken')->willReturn(
            $posts
        );

        return $postManager;
    }

    private function mockPostManagerWithoutPosts(): PostManagerInterface
    {
        return $this->createMock(PostManagerInterface::class);
    }

    private function mockNotificationPolicy(bool $canReceiveNotification): NotificationPolicyInterface
    {
        $notificationPolicy = $this->createMock(NotificationPolicyInterface::class);
        $notificationPolicy->expects($this->once())
            ->method('canReceiveNotification')
            ->willReturn($canReceiveNotification);

        return $notificationPolicy;
    }

    private function createNotification(
        bool $isAvailable,
        bool $canReceiveNotification,
        bool $isQuiet = true
    ): TokenPostNotificationStrategy {
        return new TokenPostNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, $canReceiveNotification, $isQuiet),
            $this->mockToken($isQuiet),
            [],
            self::TYPE,
            $this->mockNotificationPolicy($canReceiveNotification),
            $this->mockMailerNoSent(),
            $this->mockPostManager()
        );
    }

    private function createNotificationWithOnePost(
        bool $isAvailable,
        bool $canReceiveNotification,
        bool $isQuiet = true
    ): TokenPostNotificationStrategy {
        $posts = $this->mockPostManagerWithOnePost()->getPostsCreatedAtByToken(
            $this->createMock(Token::class),
            (new \DateTimeImmutable())
        );

        self::assertCount(
            1,
            $posts
        );

        return new TokenPostNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, $canReceiveNotification, $isQuiet),
            $this->mockToken($isQuiet),
            [],
            self::TYPE,
            $this->mockNotificationPolicy($canReceiveNotification),
            $this->mockMailerSent(),
            $this->mockPostManagerWithOnePost()
        );
    }

    private function createNotificationWithManyPosts(
        bool $isAvailable,
        bool $canReceiveNotification,
        bool $isQuiet = true
    ): TokenPostNotificationStrategy {
        $posts = $this->mockPostManagerWithManyPosts()->getPostsCreatedAtByToken(
            $this->createMock(Token::class),
            (new \DateTimeImmutable())
        );

        self::assertCount(
            self::POST_COUNT,
            $posts
        );

        return new TokenPostNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, $canReceiveNotification, $isQuiet),
            $this->mockToken($isQuiet),
            [],
            self::TYPE,
            $this->mockNotificationPolicy($canReceiveNotification),
            $this->mockMailerNoSent(),
            $this->mockPostManagerWithManyPosts()
        );
    }

    private function createNotificationWithoutPosts(
        bool $isAvailable,
        bool $canReceiveNotification,
        bool $isQuiet = true
    ): TokenPostNotificationStrategy {
        $posts = $this->mockPostManagerWithoutPosts()->getPostsCreatedAtByToken(
            $this->createMock(Token::class),
            (new \DateTimeImmutable())
        );

        self::assertCount(
            0,
            $posts
        );

        return new TokenPostNotificationStrategy(
            $this->mockUserNotificationManager($isAvailable, $canReceiveNotification, $isQuiet),
            $this->mockToken($isQuiet),
            [],
            self::TYPE,
            $this->mockNotificationPolicy($canReceiveNotification),
            $this->mockMailerNoSent(),
            $this->mockPostManagerWithoutPosts()
        );
    }
}
