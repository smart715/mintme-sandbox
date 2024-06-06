<?php declare(strict_types = 1);

namespace App\Tests\Command\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class CheckTranslationFileTest extends KernelTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);

        $command = $application->find('translation:check');

        $reflection = new \ReflectionProperty($command, 'filesystem');
        $reflection->setAccessible(true);
        $reflection->setValue($command, $this->mockFilesystem());

        $this->commandTester = new CommandTester($command);
    }

    public function testInvalidConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The configuration name argument must be a string');

        $this->commandTester->execute(['configuration'=>1]);
    }

    public function testNoConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No configuration found for "1"');

        $this->commandTester->execute(['configuration' => '1']);
    }

    public function testInvalidLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The locales argument must be a array');

        $this->commandTester->execute(['locales' => 'bla']);
    }

    public function testOneLocale(): void
    {
        $this->commandTester->execute(['locales' => ['fr']]);

        $output = $this->commandTester->getDisplay();

        $output = $this->extractOutput($output, 'compare locales');

        $output = array_values(array_filter(explode(' ', $output)));

        $this->assertEquals(['compare','locales', 'fr'], $output);
    }

    public function testNoLocale(): void
    {
        $this->commandTester->execute(['locales' => ['']]);

        $output = $this->commandTester->getDisplay();

        $output = $this->extractOutput($output, 'compare locales');

        $output = array_values(array_filter(explode(' ', $output)));

        $this->assertEquals(['compare','locales'], $output);
    }

    public function testDefault(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $output = $this->extractOutput($output, 'compare reference locale');

        $output = array_values(array_filter(explode(' ', $output)));

        $this->assertEquals(['compare','reference', 'locale', 'en'], $output);
    }

    private function extractOutput(string $input, string $word): string
    {
        $arrayOutput = explode(PHP_EOL, $input);
        $output = '';

        foreach ($arrayOutput as $line) {
            if (str_contains($line, $word)) {
                $output = $line;

                break;
            }
        }

        return $output;
    }

    private function mockFilesystem(): Filesystem
    {
        return $this->createMock(Filesystem::class);
    }
}
