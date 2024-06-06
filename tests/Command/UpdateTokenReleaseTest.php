<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateTokenRelease;
use App\Entity\Token\LockIn;
use App\Repository\LockInRepository;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class UpdateTokenReleaseTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(bool $isLockAcquired, string $expected): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new UpdateTokenRelease(
            $this->mockLogger(),
            $this->mockEntityManager(10, $isLockAcquired),
            $this->mockLockFactory($isLockAcquired),
        ));

        $command = $application->find('app:update-token-release');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return  [
            "if isLockAcquired equal true, return an expected message" => [
                "isLockAcquired" => true,
                "expected" => "10 tokens were updated. Saving to DB..",
            ],
            "if isLockAcquired equal false, return an empty message" => [
                "isLockAcquired" => false,
                "expected" => "",
            ],
        ];
    }

    private function mockEntityManager(int $lockCount, bool $isLockAcquired): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getRepository')
            ->willReturn($this->mockLockInRepository($lockCount, $isLockAcquired));

        return $entityManager;
    }

    private function mockLockInRepository(int $lockCount, bool $isLockAcquired): LockInRepository
    {
        $lockInRepository = $this->createMock(LockInRepository::class);
        $lockInRepository
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('findAllUnreleased')
            ->willReturn(array_map(function () use ($isLockAcquired) {
                return $this->mockLockIn($isLockAcquired);
            }, range(1, $lockCount)));

        return $lockInRepository;
    }

    private function mockLockIn(bool $isLockAcquired): LockIn
    {
        $lock = $this->createMock(LockIn::class);

        $lock
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('updateFrozenAmount');

        return $lock;
    }

    private function mockLock(bool $isLockAcquired): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->method('acquire')
            ->wilLReturn($isLockAcquired);

        return $lock;
    }

    private function mockLockFactory(bool $isLockAcquired): LockFactory
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->willReturn($this->mockLock($isLockAcquired));

        return $lockFactory;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
