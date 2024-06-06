<?php declare(strict_types = 1);

namespace App\Tests\Command\Translations;

use App\Command\Translations\CreateTranslationsCommand;
use App\Entity\Translation;
use App\Manager\TokenManagerInterface;
use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateTranslationsCommandTest extends KernelTestCase
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
            new CreateTranslationsCommand(
                $this->mockTranslationsManager($isExist),
                $this->mockEntityManager($success)
            )
        );

        $command = $application->find('app:create-translation');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'position' => '1000',
            'translation_for' => 'test',
            'key_language'=> 'en',
            'key_translation' => 'translation_key.1',
            'content' => 'new content',
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "Translation exists will return an error and status code equals 1" => [
                "isExist" => true,
                "expected" => "Translation already exists",
                "success" => false,
                "statusCode" => 1,
            ],
            "Translation does not exist will return a success and status code equals 0" => [
                "isExist" => false,
                "expected" => "translation_key.1 added successfully",
                "success" => true,
                "statusCode" => 0,
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
            ->method('persist')
            ->with(new Translation('test', 'en', 'translation_key.1', 'new content', '1000'));
        $entityManager
            ->expects($success ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }
}
