<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdateDisposableEmailDomains;
use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateDisposableEmailDomainsTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $this->markTestSkipped('Confliction class exists');

        return;

        $kernel = self::bootKernel();
        $app = new Application($kernel);
        $app->add(new UpdateDisposableEmailDomains(
            $this->createMock(LoggerInterface::class),
            $this->mockBlacklistManager(),
            $this->mockDomainsSynchronizer(),
            $this->mockEm()
        ));

        $command = $app->find('blacklist:synchronize-domains');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertContains("Synchronization completed.", $output);
    }

    private function mockEm(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())->method('flush');

        return $em;
    }

    private function mockBlacklistManager(): BlacklistManagerInterface
    {
        $manager = $this->createMock(BlacklistManagerInterface::class);

        $manager->expects($this->once())->method('getList')->willReturn([
            $this->mockBlacklist('foo', 'bar'),
            $this->mockBlacklist('foo', 'qux'),
        ]);

        $manager->expects($this->once())->method('addToBlacklist');

        return $manager;
    }

    private function mockDomainsSynchronizer(): DisposableEmailCommunicatorInterface
    {
        $syn = $this->createMock(DisposableEmailCommunicatorInterface::class);

        $syn->expects($this->once())->method('fetchDomains')->willReturn([
            $this->mockBlacklist('foo', 'bar'),
            $this->mockBlacklist('foo', 'baz'),
        ]);

        return $syn;
    }

    private function mockBlacklist(string $type, string $value): Blacklist
    {
        $blacklist = $this->createMock(Blacklist::class);

        $blacklist->expects($this->once())->method('getType')->willReturn($type);
        $blacklist->expects($this->once())->method('getValue')->willReturn($value);

        return $blacklist;
    }
}
