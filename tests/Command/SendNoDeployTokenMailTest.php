<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SendNoDeployTokenMail;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenRepository;
use Doctrine\ORM\AbstractQuery as Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendNoDeployTokenMailTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?string $type,
        int $invokedCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new SendNoDeployTokenMail(
                $this->mockEntityManager(),
                $this->mockMailer($type, $invokedCount),
                $this->mockTokenManager($invokedCount)
            )
        );

        $command = $application->find('app:send-no-deployed-token-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'type' => $type,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'type is set to new will return a success and status code equals 0' => [
                'type' => 'new',
                'invokedCount' => 100,
                'expected' => '100 emails have been sent',
                'statusCode' => 0,
            ],
            'type is set to null will return a success and status code equals 0' => [
                'type' => null,
                'invokedCount' => 100,
                'expected' => '100 emails have been sent',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockTokenManager(int $invokedCount): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->mockTokenRepository($invokedCount));

        return $tokenManager;
    }

    private function mockTokenRepository(int $invokedCount): TokenRepository
    {
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->mockQueryBuilder($invokedCount));

        return $tokenRepository;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('clear');

        return $entityManager;
    }

    private function mockMailer(?string $type, int $invokedCount): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($type ? $this->exactly($invokedCount) : $this->never())
            ->method('sendNotListedTokenInfoMail');
        $mailer
            ->expects(!$type ? $this->exactly($invokedCount) : $this->never())
            ->method('sendTokenRemovedFromTradingInfoMail');

        return $mailer;
    }

    private function mockQueryBuilder(int $invokedCount): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->method('where')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->method('getQuery')
            ->willReturn($this->mockQuery($invokedCount));

        return $queryBuilder;
    }

    private function mockQuery(int $invokedCount): Query
    {
        $query = $this->createMock(Query::class);
        $query
            ->method('iterate')
            ->willReturn(
                array_fill(0, $invokedCount, $this->mockToken()),
            );

        return $query;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getName')
            ->willReturn('TEST');
        $token
            ->method('getOwner')
            ->willReturn($this->mockUser());

        return $token;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
