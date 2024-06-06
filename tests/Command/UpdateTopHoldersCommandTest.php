<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateTopHoldersCommand;
use App\Entity\Token\Token;
use App\Manager\TopHolderManagerInterface;
use App\Repository\TokenRepository;
use Doctrine\ORM\AbstractQuery as Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateTopHoldersCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateTopHoldersCommand(
                $this->mockTopHolderManager(),
                $this->mockEntityManager(),
                $this->mockTokenRepository()
            )
        );

        $command = $application->find('app:top-holders:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString(
            'Top Holders were updated from 1 tokens',
            $commandTester->getDisplay()
        );
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $topHolderManager = $this->createMock(TopHolderManagerInterface::class);
        $topHolderManager
            ->method('updateTopHolders')
            ->willThrowException(new \Exception());

        $application->add(
            new UpdateTopHoldersCommand(
                $topHolderManager,
                $this->mockEntityManager(),
                $this->mockTokenRepository()
            )
        );

        $command = $application->find('app:top-holders:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString(
            'Failed to update top holders for TEST token',
            $commandTester->getDisplay()
        );
    }

    private function mockTopHolderManager(): TopHolderManagerInterface
    {
        $topHolderManager = $this->createMock(TopHolderManagerInterface::class);
        $topHolderManager
            ->expects($this->once())
            ->method('updateTopHolders');

        return $topHolderManager;
    }

    private function mockTokenRepository(): TokenRepository
    {
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->mockQueryBuilder());

        return $tokenRepository;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('flush');
        $entityManager
            ->expects($this->exactly(2))
            ->method('clear');

        return $entityManager;
    }

    private function mockQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->method('getQuery')
            ->willReturn($this->mockQuery());

        return $queryBuilder;
    }

    private function mockQuery(): Query
    {
        $query = $this->createMock(Query::class);
        $query
            ->method('iterate')
            ->willReturn([$this->mockToken()]);

        return $query;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getName')
            ->willReturn('TEST');

        return $token;
    }
}
