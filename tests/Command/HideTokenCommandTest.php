<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\HideTokenCommand;
use App\Entity\Token\Token;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HideTokenCommandTest extends KernelTestCase
{
    /** @dataProvider getTestCases */
    public function testExecute(?string $name, ?Token $token, ?bool $unhide, string $expected, bool $success): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new HideTokenCommand(
                $this->mockTokenManager($token),
                $this->mockEntityManager($token, $success)
            )
        );

        $command = $application->find('app:hide-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            "name" => $name,
            "-u" => $unhide,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expected, $output);
    }

    public function getTestCases(): array
    {
        return [
            "No name will return error" => [
                "name" => null,
                "token" => null,
                "unhide" => null,
                "expected" => "Wrong token name argument, it must be a string",
                "success" => false,
            ],
            "No token will return error" => [
                "name" => "test",
                "token" => null,
                "unhide" => null,
                "expected" => "Token 'test' not found",
                "success" => false,
            ],
            "Token is hidden and unhide is not set will return error" => [
                "name" => "test",
                "token" => $this->mockToken(true, false),
                "unhide" => false,
                "expected" => "Token 'test' is already hidden",
                "success" => false,
            ],
            "Token is unhidden and unhide is set will return error" => [
                "name" => "test",
                "token" => $this->mockToken(false, false),
                "unhide" => true,
                "expected" => "Token 'test' is not hidden",
                "success" => false,
            ],
            "Token is hidden and unhide is set will return success" => [
                "name" => "test",
                "token" => $this->mockToken(true, true),
                "unhide" => true,
                "expected" => "Token 'test' was successfully unhidden",
                "success" => true,
            ],
            "Token is unhidden and unhide is not set will return success" => [
                "name" => "test",
                "token" => $this->mockToken(false, true),
                "unhide" => false,
                "expected" => "Token 'test' was successfully hidden",
                "success" => true,
            ],

        ];
    }

    private function mockEntityManager(?Token $token, bool $success): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($success ? $this->once() : $this->never())
            ->method('persist')
            ->with($token);

        $em->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $em;
    }


    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockToken(bool $hidden, bool $commandSuccessful): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('isHidden')
            ->willReturn($hidden);

        $token->expects($commandSuccessful ? $this->once() : $this->never())
            ->method('setIsHidden');

        return $token;
    }
}
