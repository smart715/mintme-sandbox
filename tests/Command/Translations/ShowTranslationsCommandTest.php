<?php declare(strict_types = 1);

namespace App\Tests\Command\Translations;

use App\Command\Translations\ShowTranslationsCommand;
use App\Entity\Translation;
use App\Manager\TranslationsManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ShowTranslationsCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        string $translationFor,
        string $keyLanguage,
        ?string $keyTranslation,
        bool $isExist,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new ShowTranslationsCommand(
                $this->mockTranslationsManager($isExist),
            )
        );

        $command = $application->find('app:show-translation');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'translation_for' => $translationFor,
            'key_language'=> $keyLanguage,
            'key_translation' => $keyTranslation,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'Translation does not exist and keyTranslation is null will return an error and status code equals 1' => [
                'translationFor' => 'test',
                'keyLanguage' => 'en',
                'keyTranslation' => null,
                'isExist' => false,
                'expected' => 'No translations',
                'statusCode' => 1,
            ],
            'Translation does not exist and keyTranslation is not null will return an error and status code equals 1' => [
                'translationFor' => 'test',
                'keyLanguage' => 'en',
                'keyTranslation' => 'translation_key.1',
                'isExist' => false,
                'expected' => 'No translations',
                'statusCode' => 1,
            ],
            'Translation exists and keyTranslation is null will return a success and status code equals 0' => [
                'translationFor' => 'test',
                'keyLanguage' => 'en',
                'keyTranslation' => null,
                'isExist' => true,
                'expected' => '| 1000     | test            | en           | translation_key.1 | content |',
                'statusCode' => 0,
            ],
            'Translation exists and keyTranslation is not null will return a success and status code equals 0' => [
                'translationFor' => 'test',
                'keyLanguage' => 'en',
                'keyTranslation' => 'translation_key.1',
                'isExist' => true,
                'expected' => '| 1000     | test            | en           | translation_key.1 | content |',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockTranslation(): Translation
    {
        $translation = $this->createMock(Translation::class);
        $translation
            ->method('getPosition')
            ->willReturn('1000');
        $translation
            ->method('getTranslationFor')
            ->willReturn('test');
        $translation
            ->method('getKeyLanguage')
            ->willReturn('en');
        $translation
            ->method('getKeyTranslation')
            ->willReturn('translation_key.1');
        $translation
            ->method('getContent')
            ->willReturn('content');

        return $translation;
    }

    private function mockTranslationsManager(bool $isExist): TranslationsManagerInterface
    {
        $translationsManager = $this->createMock(TranslationsManagerInterface::class);
        $translationsManager
            ->method('findTranslationBy')
            ->willReturn($isExist ? $this->mockTranslation() : null);
        $translationsManager
            ->method('getAllTranslationByLanguage')
            ->willReturn($isExist ? [$this->mockTranslation()] : []);

        return $translationsManager;
    }
}
