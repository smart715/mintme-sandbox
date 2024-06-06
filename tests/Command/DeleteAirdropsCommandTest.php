<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\DeleteAirdropsCommand;
use App\Entity\AirdropCampaign\Airdrop;
use App\Manager\AirdropCampaignManager;
use App\Repository\AirdropCampaign\AirdropRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteAirdropsCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new DeleteAirdropsCommand(
                $this->mockAirdropRepository(),
                $this->mockAirdropCampaignManager(),
            )
        );

        $command = $application->find('app:delete-airdrops');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString('Airdrops deletion completed.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function mockAirdropCampaignManager(): AirdropCampaignManager
    {
        $airdropCampaignManager = $this->createMock(AirdropCampaignManager::class);
        $airdropCampaignManager
            ->expects($this->once())
            ->method('deleteAirdrop');

        return $airdropCampaignManager;
    }

    private function mockAirdropRepository(): AirdropRepository
    {
        $airdropRepository = $this->createMock(AirdropRepository::class);
        $airdropRepository
            ->expects($this->once())
            ->method('findBySingleActionType')
            ->willReturn([$this->mockAirdrop()]);
        $airdropRepository
            ->expects($this->once())
            ->method('deleteAirdropActions')
            ->willReturn(1);

        return $airdropRepository;
    }

    private function mockAirdrop(): Airdrop
    {
        return $this->createMock(Airdrop::class);
    }
}
