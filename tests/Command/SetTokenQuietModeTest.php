<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SetTokenQuietMode;
use App\Entity\Token\Token;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetTokenQuietModeTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $name,
        ?Token $token,
        ?bool $verboseMode,
        string $expected,
        bool $success,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new SetTokenQuietMode(
                $this->mockTokenManager($token),
                $this->mockEntityManager($token, $success),
                $this->mockUserActionLogger()
            )
        );

        $command = $application->find('app:quiet-mode');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                "name" => $name,
                "--verbose-mode" => $verboseMode,
            ]
        );

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "No name will return an error and status code equals 1" => [
                "name" => null,
                "token" => null,
                "verboseMode" => null,
                "expected" => "Wrong token name argument",
                "success" => false,
                "statusCode" => 1,
            ],
            "No token will return an error and status code equals 1" => [
                "name" => "test",
                "token" => null,
                "verboseMode" => null,
                "expected" => "Token doesn't exist",
                "success" => false,
                "statusCode" => 1,
            ],
            "Token is in quiet mode and verbose-mode is not set will return an error and status code equals 1" => [
                "name" => "test",
                "token" => $this->mockToken(true, false),
                "verboseMode" => false,
                "expected" => "Token is already in quiet mode",
                "success" => false,
                "statusCode" => 1,
            ],
            "Token is not in quiet mode and verbose-mode is not set will return a success and status code equals 0" => [
                "name" => "test",
                "token" => $this->mockToken(false, true),
                "verboseMode" => false,
                "expected" => "Token test was quiet mode",
                "success" => true,
                "statusCode" => 0,
            ],
            "Token is in quiet mode and verbose-mode is set will return a success and status code equals 0" => [
                "name" => "test",
                "token" => $this->mockToken(true, true),
                "verboseMode" => true,
                "expected" => "Token test was verbose mode",
                "success" => true,
                "statusCode" => 0,
            ],
        ];
    }

    private function mockToken(bool $isQuiet, bool $commandSuccessful): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getName')
            ->willReturn('test');
        $token
            ->method('isQuiet')
            ->willReturn($isQuiet);
        $token
            ->expects($commandSuccessful ? $this->once() : $this->never())
            ->method('setIsQuiet');

        return $token;
    }

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockEntityManager(?Token $token, bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('persist')
            ->with($token);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }

    private function mockUserActionLogger(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
    }
}
