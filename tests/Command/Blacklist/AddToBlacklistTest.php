<?php declare(strict_types = 1);

namespace App\Tests\Command\Blacklist;

use App\Command\Blacklist\AddToBlacklist;
use App\Manager\BlacklistManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddToBlacklistTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $manager = $this->createMock(BlacklistManagerInterface::class);

        $manager->expects($this->once())->method('addToBlacklist');

        $application->add(new AddToBlacklist($manager));

        $command = $application->find('blacklist:add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'type' => 'foo',
            'value' => 'bar',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains("Added successfuly", $output);
    }
}
