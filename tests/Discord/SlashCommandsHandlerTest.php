<?php declare(strict_types = 1);

namespace App\Tests\Discord;

use App\Discord\SlashCommands\SlashCommandInterface;
use App\Discord\SlashCommandsHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SlashCommandsHandlerTest extends TestCase
{
    public function testHandleInteraction(): void
    {
        $handler = new SlashCommandsHandler(
            $this->mockContainer($this->mockSlashCommand())
        );

        $params = ['data' => ['name' => 'test']];

        $this->assertEquals(['HANDLED'], $handler->handleInteraction($params));
    }

    public function testHandledInteractionWithNonExistingCommand(): void
    {
        $handler = new SlashCommandsHandler(
            $this->mockContainer()
        );

        $params = ['data' => ['name' => 'test']];

        $this->assertEquals(
            ['type' => 4, 'data' => ['content' => 'unknown command']],
            $handler->handleInteraction($params)
        );
    }

    private function mockContainer(?SlashCommandInterface $command = null): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->willReturn(null !== $command);

        $container->expects($command ? $this->once() : $this->never())
            ->method('get')
            ->willReturn($command);

        return $container;
    }

    private function mockSlashCommand(): SlashCommandInterface
    {
        $command = $this->createMock(SlashCommandInterface::class);
        $command->expects($this->once())
            ->method('handleInteraction')
            ->willReturn(['HANDLED']);

        return $command;
    }
}
