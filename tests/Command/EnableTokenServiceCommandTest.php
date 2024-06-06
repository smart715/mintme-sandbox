<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\EnableTokenServiceCommand;
use App\Entity\Token\Token;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class EnableTokenServiceCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $tokenName,
        ?Token $token,
        string $deposits,
        string $withdrawals,
        string $trades,
        bool $success,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new EnableTokenServiceCommand(
                $this->mockTokenManager($token),
                $this->mockEntityManager($success)
            )
        );

        $command = $application->find('token:set-service-enabled');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'token' => $tokenName,
            '--deposits' => $deposits,
            '--withdrawals' => $withdrawals,
            '--trades' => $trades,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'token name is not a string format will return an error and status code equals 1' => [
                'tokenName' => null,
                'token' => null,
                'deposits' => 'no',
                'withdrawals' => 'no',
                'trades' => 'no',
                'success' => false,
                'expected' => 'Wrong token name argument, it must be a string!',
                'statusCode' => 1,
            ],
            'token does not exist will return an error and status code equals 1' => [
                'tokenName' => 'FOO',
                'token' => null,
                'deposits' => 'no',
                'withdrawals' => 'no',
                'trades' => 'no',
                'success' => false,
                'expected' => 'Token \'FOO\' not found!',
                'statusCode' => 1,
            ],
            'type a wrong value will return an error and status code equals 1' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => 'No',
                'withdrawals' => 'No',
                'trades' => 'No',
                'success' => false,
                'expected' => 'Wrong input',
                'statusCode' => 1,
            ],
            'deposits, withdrawals and trades options are not set will return an error and status code equals 1' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => '',
                'withdrawals' => '',
                'trades' => '',
                'success' => false,
                'expected' => 'You didnt pick any service to change! Nothing will be changed',
                'statusCode' => 1,
            ],
            'deposits option is set with "yes" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => 'yes',
                'withdrawals' => '',
                'trades' => '',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'deposits option is set with "no" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => 'no',
                'withdrawals' => '',
                'trades' => '',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'withdrawals option is set with "yes" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => '',
                'withdrawals' => 'yes',
                'trades' => '',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'withdrawals option is set with "no" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => '',
                'withdrawals' => 'no',
                'trades' => '',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'trades option is set with "yes" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => '',
                'withdrawals' => '',
                'trades' => 'yes',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'trades option is set with "no" value will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => '',
                'withdrawals' => '',
                'trades' => 'no',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'deposits, withdrawals and trades options are set with "yes" values will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => 'yes',
                'withdrawals' => 'yes',
                'trades' => 'yes',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
            'deposits, withdrawals and trades options are set with "no" values will return a success and status code equals 0' => [
                'tokenName' => 'FOO',
                'token' => $this->mockToken(),
                'deposits' => 'no',
                'withdrawals' => 'no',
                'trades' => 'no',
                'success' => true,
                'expected' => 'FOO service updated successfully',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockEntityManager(bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('persist');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getName')
            ->willReturn('FOO');

        return $token;
    }
}
