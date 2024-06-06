<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SetParameterCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class SetParameterCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private string $parametersPath;

    public function setUp(): void
    {
        // Create a temporary file to use as the parameters file
        $this->parametersPath = (string)tempnam(sys_get_temp_dir(), 'params');


        // Initialize the parameters file with some data
        $data = [
            'parameters' => [
                'param1' => 'value1',
                'param2' => [
                    'subparam' => 'value2',
                ],
                'param3' => [
                    0 => 'value1',
                    1 => 'value2',
                    2 => 'value3',
                ],
            ],
        ];

        file_put_contents($this->parametersPath, Yaml::dump($data));

        $setParameterCommand = new SetParameterCommand($this->parametersPath);

        $kernel = self::bootKernel();
        $app = new Application($kernel);
        $app->add($setParameterCommand);

        $command = $app->find('app:set-parameter');
        $this->commandTester = new CommandTester($command);
    }

    public function tearDown(): void
    {
        unlink($this->parametersPath);
    }

    /**
     * @dataProvider parameterDataProvider
     */
    public function testExecuteWithValidParameters(string $name, string $value, bool $remove = false): void
    {
        $this->commandTester->execute([
            'name' => $name,
            '--value' => $value,
            '--remove' => $remove,
        ]);

        if ($remove) {
            self::assertStringContainsString(
                sprintf("'%s'\" was removed from \"%s", $value, $name),
                $this->commandTester->getDisplay()
            );

            return;
        }

        self::assertStringContainsString(
            sprintf('Parameter "%s" set to "\'%s\'"', $name, $value),
            $this->commandTester->getDisplay()
        );
    }

    public function testExecuteWithInvalidParameters(): void
    {
        $this->expectException(\RuntimeException::class);
    
        $this->commandTester->execute([
            'name' => 'invalid_parameter',
        ]);
    
        // Check if the command returns a failure exit code
        self::assertSame(1, $this->commandTester->getStatusCode());
    }

    public function parameterDataProvider(): array
    {
        return [
            'Set param1 to test1' => [
                'name' => 'param1',
                'value' => 'test1',
            ],
            'Set param2.subparam to test2' => [
                'name' => 'param2.subparam',
                'value' => 'test2',
            ],
            'Add test3 to param3 array' => [
                'name' => 'param3',
                'value' => 'test3',
            ],
            'Remove value3 from param3' => [
                'name' => 'param3',
                'value' => 'value3',
                'remove' => true,
            ],
        ];
    }
}
