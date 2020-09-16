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
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $lockCount = 10;

        $application->add(new UpdateTokenRelease(
            $this->createMock(LoggerInterface::class),
            $this->mockEm($lockCount),
            $this->mockLockFactory()
        ));

        $command = $application->find('app:update-token-release');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertContains("${lockCount} tokens were updated. Saving to DB..", $output);
    }

    private function mockEm(int $lockCount): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $repo = $this->createMock(LockInRepository::class);
        $repo->expects($this->once())
            ->method('findAllUnreleased')
            ->willReturn(array_map(function () {
                return $this->mockLockIn();
            }, range(1, $lockCount)));

        $em->method('getRepository')->willReturn($repo);

        return $em;
    }

    private function mockLockIn(): LockIn
    {
        $lock = $this->createMock(LockIn::class);

        $lock->expects($this->once())->method('updateFrozenAmount');

        return $lock;
    }

    private function mockLockFactory(): LockFactory
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->wilLReturn(true);

        $lf = $this->createMock(LockFactory::class);
        $lf->method('createLock')->willReturn($lock);

        return $lf;
    }
}
