<?php declare(strict_types = 1);

namespace App\Tests\Command\Translations;

use App\Command\Translations\DeleteTranslationsCommand;
use App\Entity\Translation;
use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteTranslationsCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        bool $isExist,
        string $expected,
        bool $success,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new DeleteTranslationsCommand(
                $this->mockTranslationsManager($isExist),
                $this->mockEntityManager($success)
            )
        );

        $command = $application->find('app:delete-translation');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'translation_for' => 'test',
            'key_language'=> 'en',
            'key_translation' => 'translation_key.1',
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            'Translation does not exist will return an error and status code equals 1' => [
                'isExist' => false,
                'expected' => 'The translation does not exist',
                'success' => false,
                'statusCode' => 1,
            ],
            'Translation exists will return a success and status code equals 0' => [
                'isExist' => true,
                'expected' => 'translation_key.1 has been successfully removed',
                'success' => true,
                'statusCode' => 0,
            ],
        ];
    }

    private function mockTranslation(): Translation
    {
        return $this->createMock(Translation::class);
    }

    private function mockTranslationsManager(bool $isExist): TranslationsManagerInterface
    {
        $translationsManager = $this->createMock(TranslationsManagerInterface::class);
        $translationsManager
            ->method('findTranslationBy')
            ->willReturn($isExist ? $this->mockTranslation() : null);

        return $translationsManager;
    }

    private function mockEntityManager(bool $success): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('remove');
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }
}
