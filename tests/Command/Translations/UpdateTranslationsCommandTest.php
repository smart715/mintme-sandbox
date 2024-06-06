<?php declare(strict_types = 1);

namespace App\Tests\Command\Translations;

use App\Command\Translations\UpdateTranslationsCommand;
use App\Entity\Translation;
use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateTranslationsCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        ?Translation $translation,
        string $expected,
        bool $success,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateTranslationsCommand(
                $this->mockTranslationsManager($translation),
                $this->mockEntityManager($translation, $success)
            )
        );

        $command = $application->find('app:update-translation');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'translation_for' => 'test',
            'key_language' => 'en',
            'key_translation' => 'translation_key.1',
            'content' => 'new content',
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'Translation does not exist will return an error and status code equals 1' => [
                'translation' => null,
                'expected' => 'The translation does not exist',
                'success' => false,
                'statusCode' => 1,
            ],
            'Translation exists will return a success and status code equals 0' => [
                'translation' => $this->mockTranslation(),
                'expected' => 'translation_key.1 has been successfully updated',
                'success' => true,
                'statusCode' => 0,
            ],
        ];
    }

    private function mockTranslation(): Translation
    {
        $translation = $this->createMock(Translation::class);
        $translation
            ->expects($this->once())
            ->method('setContent');

        return $translation;
    }

    private function mockTranslationsManager(?Translation $translation): TranslationsManagerInterface
    {
        $translationsManager = $this->createMock(TranslationsManagerInterface::class);
        $translationsManager
            ->method('findTranslationBy')
            ->willReturn($translation);

        return $translationsManager;
    }

    private function mockEntityManager(?Translation $translation, bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('persist')
            ->with($translation);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }
}
