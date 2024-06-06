<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Translation;
use App\Manager\TranslationsManager;
use App\Repository\TranslationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TranslationsManagerTest extends TestCase
{
    public function testGetAllTranslationByLanguage(): void
    {
        $translationFor = 'test';
        $keyLanguage = 'en';

        $translation = $this->mockTranslation();

        $translationRepository = $this->mockTranslationRepository();
        $translationRepository
            ->expects($this->once())
            ->method('getAllTranslationByLanguageIndexedByPosition')
            ->with($translationFor, $keyLanguage)
            ->willReturn([$translation]);

        $translationsManager = new TranslationsManager($translationRepository);

        $this->assertEquals(
            [$translation],
            $translationsManager->getAllTranslationByLanguage($translationFor, $keyLanguage, true)
        );
    }

    public function testFindTranslationBy(): void
    {
        $translationFor = 'test';
        $keyLanguage = 'en';
        $keyTranslation = 'key';

        $translation = $this->mockTranslation();

        $translationRepository = $this->mockTranslationRepository();
        $translationRepository
            ->expects($this->exactly(2))
            ->method('findTranslationBy')
            ->willReturnOnConsecutiveCalls($translation, null);

        $translationsManager = new TranslationsManager($translationRepository);

        $this->assertEquals(
            $translation,
            $translationsManager->findTranslationBy($translationFor, $keyLanguage, $keyTranslation)
        );
        $this->assertNull($translationsManager->findTranslationBy($translationFor, $keyLanguage, $keyTranslation));
    }

    private function mockTranslation(): Translation
    {
        return $this->createMock(Translation::class);
    }

    /** @return TranslationRepository|MockObject */
    private function mockTranslationRepository(): TranslationRepository
    {
        return $this->createMock(TranslationRepository::class);
    }
}
