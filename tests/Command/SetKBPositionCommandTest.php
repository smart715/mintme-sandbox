<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SetKBPositionCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetKBPositionCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new SetKBPositionCommand(
            $this->mockEntityManager(
                $this->mockStatement(),
                true
            )
        ));

        $command = $application->find('app:kb:position');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('Correct positions are set', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('fetchAll')
            ->willThrowException(new \Exception());

        $application->add(new SetKBPositionCommand(
            $this->mockEntityManager(
                $statement,
                false
            )
        ));

        $command = $application->find('app:kb:position');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('An error occurred', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    private function mockEntityManager(Statement $statement, bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getConnection')
            ->willReturn($this->mockConnection($statement, $success));

        return $entityManager;
    }

    private function mockConnection(Statement $statement, bool $success): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('prepare')
            ->willReturn($statement);
        $connection
            ->expects($this->once())
            ->method('beginTransaction');
        $connection
            ->expects($success ? $this->once() : $this->never())
            ->method('commit');
        $connection
            ->expects($success ? $this->never() : $this->once())
            ->method('rollBack');

        return $connection;
    }

    private function mockStatement(): Statement
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->method('fetchAll')
            ->willReturn([['id' => 'test']]);

        return $statement;
    }
}
