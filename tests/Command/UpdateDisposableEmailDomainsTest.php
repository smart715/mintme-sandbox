<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateDisposableEmailDomains;
use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManagerInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class UpdateDisposableEmailDomainsTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        bool $isLockAcquired,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application ->add(new UpdateDisposableEmailDomains(
            $this->mockLogger(),
            $this->mockBlacklistManager($isLockAcquired),
            $this->mockDisposableEmailCommunicator($isLockAcquired),
            $this->mockEntityManager($isLockAcquired),
            $this->mockLockFactory($isLockAcquired)
        ));

        $command = $application->find('app:synchronize-domains');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return  [
            'if isLockAcquired equal false, return an empty message' => [
                'isLockAcquired' => false,
                'expected' => '',
                'statusCode' => 0,
            ],
            'if isLockAcquired equal true, return an expected message' => [
                'isLockAcquired' => true,
                'expected' => 'Synchronization completed',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockEntityManager(bool $isLockAcquired): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($isLockAcquired ? $this->exactly(2) : $this->never())
            ->method('flush');

        return $entityManager;
    }

    private function mockBlacklistManager(bool $isLockAcquired): BlacklistManagerInterface
    {
        $blacklistManager = $this->createMock(BlacklistManagerInterface::class);
        $blacklistManager
            ->expects($isLockAcquired ? $this->exactly(2) : $this->never())
            ->method('add');
        $blacklistManager
            ->expects($isLockAcquired ? $this->exactly(2) : $this->never())
            ->method('getList')
            ->willReturn([
                $this->mockBlacklist('foo', 'bar'),
                $this->mockBlacklist('foo', 'qux'),
            ]);

        return $blacklistManager;
    }

    private function mockDisposableEmailCommunicator(
        bool $isLockAcquired
    ): DisposableEmailCommunicatorInterface {
        $disposableEmailCommunicator = $this->createMock(DisposableEmailCommunicatorInterface::class);
        $disposableEmailCommunicator
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('fetchDomainsIndex')
            ->willReturn(['foo', 'bar']);
        $disposableEmailCommunicator
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('fetchDomainsWildcard')
            ->willReturn(['foo', 'bar']);

        return $disposableEmailCommunicator;
    }

    private function mockBlacklist(string $type, string $value): Blacklist
    {
        $blacklist = $this->createMock(Blacklist::class);
        $blacklist
            ->method('getType')
            ->willReturn($type);
        $blacklist
            ->method('getValue')
            ->willReturn($value);

        return $blacklist;
    }

    private function mockLock(bool $isLockAcquired): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->method('acquire')
            ->willReturn($isLockAcquired);
        $lock
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('release');

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
