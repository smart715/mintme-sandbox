<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\ConsoleCommandsListener;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\MarketStatusManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandsListenerTest extends TestCase
{
    public function testOnConsoleTerminateWithDoctrine(): void
    {
        $e = $this->createMock(ConsoleTerminateEvent::class);

        $c = $this->createMock(Command::class);
        $c->method('getName')->willReturn('doctrine:migrations:migrate');

        $e->method('getCommand')->willReturn($c);
        $e->method('getOutput')->willReturn($this->createMock(OutputInterface::class));

        $manager = $this->createMock(MarketStatusManagerInterface::class);
        $manager->expects($this->once())->method('createMarketStatus');

        $listener = new ConsoleCommandsListener(
            $this->createMock(MarketFactoryInterface::class),
            $manager
        );

        $listener->onConsoleTerminate($e);
    }

    public function testOnConsoleTerminate(): void
    {
        $e = $this->createMock(ConsoleTerminateEvent::class);

        $c = $this->createMock(Command::class);
        $c->method('getName')->willReturn('foo');

        $e->method('getCommand')->willReturn($c);
        $e->method('getOutput')->willReturn($this->createMock(OutputInterface::class));

        $manager = $this->createMock(MarketStatusManagerInterface::class);
        $manager->expects($this->never())->method('createMarketStatus');

        $listener = new ConsoleCommandsListener(
            $this->createMock(MarketFactoryInterface::class),
            $manager
        );

        $listener->onConsoleTerminate($e);
    }
}
