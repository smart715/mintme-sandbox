<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\BlockAccountCommand;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\OrderManagerInterface;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BlockAccountCommandTest extends KernelTestCase
{
    private Application $app;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    /**
     * @dataProvider getTestCases
     */
    public function testExecute(
        string $email,
        ?Token $token,
        bool $userExists,
        bool $invalidEmail
    ): void {
        $this->app->add(new BlockAccountCommand(
            $this->mockUserManager($userExists, $email, $token),
            $this->mockEntityManager($userExists),
            $this->mockUserActionLogger(),
            $this->mockOrderManager()
        ));

        $command = $this->app->find('app:block-account');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['email' => $email]);

        $output = $commandTester->getDisplay();

        if ($userExists) {
            if ($token) {
                $this->assertStringContainsString("{$token->getName()} token blocked", $output);
            }

            $this->assertStringContainsString("Orders cancelled", $output);
            $this->assertStringContainsString("user nickname removed", $output);
            $this->assertStringContainsString("Account of $email was blocked", $output);
            $this->assertStringNotContainsString("Failed to rename", $output);
            $this->assertStringContainsString("Username field renamed", $output);
            $this->assertStringContainsString("UsernameCanonical field renamed", $output);
            $this->assertStringContainsString("Email field renamed", $output);
            $this->assertStringContainsString("EmailCanonical field renamed", $output);
        } else {
            $invalidEmail
                ? $this->assertStringContainsString("Wrong email argument", $output)
                : $this->assertStringContainsString("User $email doesn't exist", $output);
        }
    }

    public function getTestCases(): array
    {
        return [
            'Blocking user with no token' => [
                'email' => 'Test@test.com',
                'token' => null,
                'userExists' => true,
                'invalidEmail' => false,
            ],
            'Blocking User exist with a token' =>  [
                'email' => 'Test@test.com',
                'token' => $this->mockToken(),
                'userExists' => true,
                'invalidEmail' => false,
            ],
            'Blocking non registered email' => [
                'email' => 'Test@test.com',
                'token' => null,
                'userExists' => false,
                'invalidEmail' => false,
            ],
            'Blocking Invalid email does not process' => [
                'email' => 'invalidEmail',
                'token' => null,
                'userExists' => false,
                'invalidEmail' => true,
            ],
        ];
    }

    private function mockOrderManager(): OrderManagerInterface
    {
        return $this->createMock(OrderManagerInterface::class);
    }

    private function mockUserManager(bool $userExists, string $email, ?Token $token): UserManagerInterface
    {
        $manager = $this->createMock(UserManagerInterface::class);
        $manager
            ->method('findUserByEmail')
            ->willReturn($userExists ? $this->mockUser($email, $token) : null);

        return $manager;
    }

    private function mockUser(string $email, ?Token $token): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getProfile')
            ->willReturn($this->mockProfile($token));
        $user
            ->method('getEmail')
            ->willReturn($email);

        return $user;
    }

    private function mockProfile(?Token $token): Profile
    {
        $profile = $this->createMock(Profile::class);

        if ($token) {
            $profile
                ->method('getTokens')
                ->willReturn([$token]);
        }

        return $profile;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('setIsBlocked');

        return $token;
    }

    private function mockEntityManager(bool $success): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($success ? $this->once() : $this->never())
            ->method('persist');
        $em
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $em;
    }

    private function mockUserActionLogger(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
    }
}
