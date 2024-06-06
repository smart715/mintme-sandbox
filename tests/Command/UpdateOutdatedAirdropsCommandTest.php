<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateOutdatedAirdropsCommand;
use App\Manager\AirdropCampaignManagerInterface;
use App\Utils\LockFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class UpdateOutdatedAirdropsCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(bool $isLockAcquired, string $expected): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $airdropCampaignManager = $this->mockAirdropCampaignManager();
        $airdropCampaignManager
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('updateOutdatedAirdrops')
            ->willReturn(1);

        $application->add(new UpdateOutdatedAirdropsCommand(
            $airdropCampaignManager,
            $this->mockLockFactory($isLockAcquired),
        ));

        $command = $application->find('app:update-outdated-airdrops');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
    }

    public function executeDataProvider(): array
    {
        return  [
            "if isLockAcquired equal true, return an expected message" => [
                "isLockAcquired" => true,
                "expected" => "1 airdrops updated.",
            ],
            "if isLockAcquired equal false, return an empty message" => [
                "isLockAcquired" => false,
                "expected" => "",
            ],
        ];
    }

    /** @return AirdropCampaignManagerInterface|MockObject */
    private function mockAirdropCampaignManager(): AirdropCampaignManagerInterface
    {
        return $this->createMock(AirdropCampaignManagerInterface::class);
    }

    private function mockLock(bool $isLockAcquired): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->wilLReturn($isLockAcquired);

        return $lock;
    }

    private function mockLockFactory(bool $isLockAcquired): LockFactory
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($this->mockLock($isLockAcquired));

        return $lockFactory;
    }
}
