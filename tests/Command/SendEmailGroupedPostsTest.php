<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SendEmailGroupedPosts;
use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\Policy\NotificationPolicyInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendEmailGroupedPostsTest extends KernelTestCase
{
    /**
     * @dataProvider executeDataProvider
     * @param string|int|null $date
     */
    public function testExecute(
        $date,
        bool $isSend,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $token = $this->mockToken('TEST', $this->mockUser(99999), [$this->mockUser(1)]);

        $posts = [
            $this->mockPost($token),
            $this->mockPost($token),
            $this->mockPost($token),
        ];

        $application->add(
            new SendEmailGroupedPosts(
                $this->mockMailer($posts[0]->getToken()->getName(), $isSend),
                $this->mockPostManager($posts),
                $this->mockTokenManager($token),
                $this->mockUserNotificationManager(),
                $this->mockNotificationPolicy()
            )
        );

        $command = $application->find('app:send-grouped-posts');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'date' => $date,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'Date is not a string format will return an error and status code equals 1' => [
                'date' => 23-01-01,
                'isSend' => false,
                'expected' => 'Wrong date argument',
                'statusCode' => 1,
            ],
            'Date is not valid will return an error and status code equals 1' => [
                'date' => '23-01-01',
                'isSend' => false,
                'expected' => '23-01-01: is not a valid date',
                'statusCode' => 1,
            ],
            'Date is null will return a success and status code equals 0' => [
                'date' => null,
                'isSend' => true,
                'expected' => 'Emails has been sent',
                'statusCode' => 0,
            ],
            'Date is not null and valid will return a success and status code equals 0' => [
                'date' => '2023-01-01',
                'isSend' => true,
                'expected' => 'Emails has been sent',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn($id);

        return $user;
    }

    private function mockToken(string $name, User $owner, array $users): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getName')
            ->willReturn($name);
        $token
            ->method('getOwner')
            ->willReturn($owner);
        $token
            ->method('getUsers')
            ->willReturn($users);

        return $token;
    }

    private function mockPost(Token $token): Post
    {
        $post = $this->createMock(Post::class);
        $post
            ->method('getToken')
            ->willReturn($token);

        return $post;
    }

    private function mockMailer(string $tokenName, bool $isSend): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($isSend ? $this->once() : $this->never())
            ->method('sendGroupedPosts')
            ->with($this->anything(), $tokenName, $this->anything());

        return $mailer;
    }

    private function mockPostManager(array $posts): PostManagerInterface
    {
        $postManager = $this->createMock(PostManagerInterface::class);
        $postManager
            ->method('getPostsCreatedAt')
            ->willReturn($posts);

        return $postManager;
    }

    private function mockTokenManager(Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockUserNotificationManager(): UserNotificationManagerInterface
    {
        $userNotificationManager = $this->createMock(UserNotificationManagerInterface::class);
        $userNotificationManager
            ->method('isNotificationAvailable')
            ->willReturn(true);

        return $userNotificationManager;
    }

    private function mockNotificationPolicy(): NotificationPolicyInterface
    {
        $notificationPolicy = $this->createMock(NotificationPolicyInterface::class);
        $notificationPolicy
            ->method('canReceiveNotification')
            ->willReturn(true);

        return $notificationPolicy;
    }
}
