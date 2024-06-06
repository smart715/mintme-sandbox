<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateVotingSlugsCommand;
use App\Entity\Voting\Voting;
use App\Repository\VotingRepository;
use App\Utils\Converter\SlugConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateVotingSlugsCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $count = 1;

        $application->add(new UpdateVotingSlugsCommand(
            $this->mockVotingRepository([$this->mockVoting()]),
            $this->mockEntityManager(true),
            $this->mockSlugConverter()
        ));

        $command = $application->find('app:update-voting-slugs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString(
            "We updated {$count} votings successfully.",
            $commandTester->getDisplay()
        );
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $slugConverter = $this->mockSlugConverter();
        $slugConverter
            ->method('convert')
            ->willThrowException(new Exception());

        $application->add(new UpdateVotingSlugsCommand(
            $this->mockVotingRepository([$this->mockVoting()]),
            $this->mockEntityManager(false),
            $slugConverter
        ));

        $this->expectException(\Throwable::class);

        $command = $application->find('app:update-voting-slugs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    /** @return MockObject|VotingRepository */
    private function mockVotingRepository(array $votings): VotingRepository
    {
        $tokenVotingRepository = $this->createMock(VotingRepository::class);
        $tokenVotingRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['slug' => null])
            ->willReturn($votings);

        return $tokenVotingRepository;
    }

    /** @return MockObject|EntityManagerInterface */
    private function mockEntityManager(bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('beginTransaction');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('persist');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('commit');
        $entityManager
            ->expects(!$success ? $this->once() : $this->never())
            ->method('rollback');

        return $entityManager;
    }

    /** @return MockObject|SlugConverterInterface */
    private function mockSlugConverter(): SlugConverterInterface
    {
        return $this->createMock(SlugConverterInterface::class);
    }

    private function mockVoting(): Voting
    {
        return $this->createMock(Voting::class);
    }
}
