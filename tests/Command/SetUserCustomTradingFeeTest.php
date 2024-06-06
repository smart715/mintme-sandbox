<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SetUserCustomTradingFee;
use App\Entity\User;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetUserCustomTradingFeeTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $userName,
        ?User $user,
        ?bool $resetToDefault,
        ?string $fee,
        string $expected,
        bool $success,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new SetUserCustomTradingFee(
                $this->mockEntityManager($user, $success),
                $this->mockUserManager($user)
            )
        );

        $command = $application->find('app:custom-fee');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'username' => $userName,
            '--reset-to-default' => $resetToDefault,
            '--fee' => $fee,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "No username will return an error and status code equals 1" => [
                "userName" => null,
                "user" => null,
                "resetToDefault" => null,
                "fee" => null,
                "expected" => "Wrong username name argument",
                "success" => false,
                "statusCode" => 1,
            ],
            "No user will return an error and status code equals 1" => [
                "userName" => "test",
                "user" => null,
                "resetToDefault" => null,
                "fee" => null,
                "expected" => "User doesn't exist",
                "success" => false,
                "statusCode" => 1,
            ],
            "User exists and fee is not a numerical value will return an error and status code equals 1" => [
                "userName" => "test",
                "user" => $this->mockUser(false),
                "resetToDefault" => false,
                "fee" => "a",
                "expected" => "Wrong fee value, please check",
                "success" => false,
                "statusCode" => 1,
            ],
            "User exists and fee is less than zero will return an error and status code equals 1" => [
                "userName" => "test",
                "user" => $this->mockUser(false),
                "resetToDefault" => false,
                "fee" => "-1",
                "expected" => "Fee value should be between 0 and slightly less than 1 (~100%)",
                "success" => false,
                "statusCode" => 1,
            ],
            "User exists and fee is equal to 1 will return an error and status code equals 1" => [
                "userName" => "test",
                "user" => $this->mockUser(false),
                "resetToDefault" => false,
                "fee" => "1",
                "expected" => "Fee value should be between 0 and slightly less than 1 (~100%)",
                "success" => false,
                "statusCode" => 1,
            ],
            "User exists and fee is greater than 1 will return an error and status code equals 1" => [
                "userName" => "test",
                "user" => $this->mockUser(false),
                "resetToDefault" => false,
                "fee" => "2",
                "expected" => "Fee value should be between 0 and slightly less than 1 (~100%)",
                "success" => false,
                "statusCode" => 1,
            ],
            "User exists and fee is between 0 and less than 1 will return a success and status code equals 0" => [
                "userName" => "test",
                "user" => $this->mockUser(true),
                "resetToDefault" => false,
                "fee" => "0.5",
                "expected" => "Custom maker/taker fee was set in 0.5",
                "success" => true,
                "statusCode" => 0,
            ],
            "User exists and reset-to-default is true will return a success and status code equals 0" => [
                "userName" => "test",
                "user" => $this->mockUser(true),
                "resetToDefault" => true,
                "fee" => null,
                "expected" => "Custom maker/taker fee was set in default",
                "success" => true,
                "statusCode" => 0,
            ],
        ];
    }

    private function mockUser(bool $commandSuccessful): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getUsername')
            ->willReturn('test');
        $user
            ->expects($commandSuccessful ? $this->once() : $this->never())
            ->method('setTradingFee');

        return $user;
    }

    private function mockUserManager(?User $user): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->method('findUserByEmail')
            ->willReturn($user);

        return $userManager;
    }

    private function mockEntityManager(?User $user, bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('persist')
            ->with($user);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }
}
