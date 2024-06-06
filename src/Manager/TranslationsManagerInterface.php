<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Translation;

interface TranslationsManagerInterface
{
    public function findTranslationBy(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation
    ): ?Translation;

    public function getAllTranslationByLanguage(
        string $translationFor,
        string $keyLanguage,
        bool $replaceNonExistentOnEn
    ): array;
}
