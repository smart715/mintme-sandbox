<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SendCustomMailCommand;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendCustomMailCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $userMailAddress,
        ?string $mailToSend,
        ?User $user,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new SendCustomMailCommand(
                $this->mockUserManager($user),
                $this->mockMailer(),
                '1',
                '1',
                'FOO'
            )
        );

        $command = $application->find('app:sendCustomMail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'userMailAddress' => $userMailAddress,
            'mailToSend' => $mailToSend,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'user mail address does not exist will return an error and status code equals 1' => [
                'userMailAddress' => null,
                'mailToSend' => 'MintmeHost',
                'user' => $this->mockUser(),
                'expected' => 'Wrong user address mail or mail name argument',
                'statusCode' => 1,
            ],
            'user mail address is not valid will return an error and status code equals 1' => [
                'userMailAddress' => 'user',
                'mailToSend' => 'MintmeHost',
                'user' => $this->mockUser(),
                'expected' => 'Wrong user address mail or mail name argument',
                'statusCode' => 1,
            ],
            'mail does not exist will return an error and status code equals 1' => [
                'userMailAddress' => 'user@example.com',
                'mailToSend' => null,
                'user' => $this->mockUser(),
                'expected' => 'Wrong user address mail or mail name argument',
                'statusCode' => 1,
            ],
            'user does not exist will return a warning and status code equals 1' => [
                'userMailAddress' => 'user@example.com',
                'mailToSend' => 'MintmeHost',
                'user' => null,
                'expected' => 'the email is not registered on mintme.com',
                'statusCode' => 1,
            ],
            'mail is not vaild will return a warning and status code equals 1' => [
                'userMailAddress' => 'user@example.com',
                'mailToSend' => 'invalidMail',
                'user' => $this->mockUser(),
                'expected' => 'the mail does not exist or is not available',
                'statusCode' => 1,
            ],
            'mail is vaild will return a success and status code equals 0' => [
                'userMailAddress' => 'user@example.com',
                'mailToSend' => 'MintmeHost',
                'user' => $this->mockUser(),
                'expected' => 'the email has ben sent to user@example.com',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockUserManager(?User $user): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->method('findUserByEmail')
            ->willReturn($user);

        return $userManager;
    }

    private function mockMailer(): MailerInterface
    {
        return $this->createMock(MailerInterface::class);
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getEmail')
            ->willReturn('user@example.com');

        return $user;
    }
}
