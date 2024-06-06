<?php declare(strict_types = 1);

namespace App\Tests\Command\Blacklist;

use App\Command\Blacklist\AddToBlacklist;
use App\Manager\BlacklistManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AddToBlacklistTest extends KernelTestCase
{
    /** @var Command */
    private $command;

    /** @var BlacklistManagerInterface|MockObject */
    private $manager;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $this->manager = $this->createMock(BlacklistManagerInterface::class);
        $application->add(new AddToBlacklist($this->manager));

        $this->command = $application->find('blacklist:add');
    }
    public function testSuccess(): void
    {

        $this->manager->expects($this->once())->method('add');
        $commandTester = new CommandTester($this->command);

        $commandTester->execute([
            'type' => 'token',
            'value' => 'bar',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("Added successfully", $output);
    }

    public function testInvalidType(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->manager->expects($this->never())->method('add');

        $commandTester->execute([
            'type' => 'foo',
            'value' => 'bar',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("Not supported type", $output);
    }

    public function testInvalidValueIfTypeIsAirdropAndDomainHasWWW(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->manager->expects($this->never())->method('add');

        $commandTester->execute([
            'type' => 'airdrop-domain',
            'value' => 'www.example.com',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("please add the domain without \"www\"", $output);
    }

    public function testSuccessWithAirdropType(): void
    {
        $this->manager->expects($this->exactly(2))->method('add');
        $commandTester = new CommandTester($this->command);

        $commandTester->execute([
            'type' => 'airdrop-domain',
            'value' => 'example.com',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString("Added successfully", $output);
    }
}
