<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\CreateDiscordCommand;
use App\Communications\RestRpcInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateDiscordCommandTest extends KernelTestCase
{
    private Application $app;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    /** @dataProvider getTestCases */
    public function testExecute(
        ?string $name,
        ?string $clientId,
        ?string $path
    ): void {
        $this->app->add(new CreateDiscordCommand($this->mockGuzzle($path), $clientId));

        $command = $this->app->find('app:create-discord-command');

        $commandTester = new CommandTester($command);
        
        $commandTester->execute(['name' => $name]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Success', $output);
    }

    public function getTestCases(): array
    {
        return  [
            "No1" => [
                "name" => 'test1',
                "clientId" => "1",
                "path" => "1/commands",
            ],
            "No2" => [
                "name" => "test2",
                "clientId" => "2",
                "path" => "2/commands",
            ],
        ];
    }

    private function mockGuzzle(string $path): RestRpcInterface
    {
        $guzzle = $this->createMock(RestRpcInterface::class);

        $guzzle
            ->expects($this->once())
            ->method('send')
            ->with($path);

        return $guzzle;
    }
}
