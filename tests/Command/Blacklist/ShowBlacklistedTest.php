<?php declare(strict_types = 1);

namespace App\Tests\Command\Blacklist;

use App\Command\Blacklist\ShowBlacklisted;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ShowBlacklistedTest extends KernelTestCase
{
    /** @var Application */
    private $app;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    public function testExecute(): void
    {
        $manager = $this->createMock(BlacklistManagerInterface::class);

        $manager->expects($this->once())->method('getList')->willReturn([
            $this->mockBlacklist('foo', 'bar'),
            $this->mockBlacklist('baz', 'qux'),
        ]);

        $this->app->add(new ShowBlacklisted($manager));

        $command = $this->app->find('blacklist:show');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertContains("type   value", $output);
        $this->assertContains("foo    bar", $output);
        $this->assertContains("baz    qux", $output);
    }

    public function testExecuteWithoutData(): void
    {
        $manager = $this->createMock(BlacklistManagerInterface::class);

        $manager->expects($this->once())->method('getList')->willReturn([]);

        $this->app->add(new ShowBlacklisted($manager));

        $command = $this->app->find('blacklist:show');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertContains("No entries found", $output);
    }

    private function mockBlacklist(string $type, string $value): Blacklist
    {
        $blacklist = $this->createMock(Blacklist::class);

        $blacklist->expects($this->once())->method('getType')->willReturn($type);
        $blacklist->expects($this->once())->method('getValue')->willReturn($value);

        return $blacklist;
    }
}
