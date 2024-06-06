<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Translation;
use App\Entity\User;
use App\Repository\TranslationRepository;

class TranslationsManager implements TranslationsManagerInterface
{
    private TranslationRepository $translationRepository;

    public function __construct(
        TranslationRepository $translationRepository
    ) {
        $this->translationRepository = $translationRepository;
    }

    public function getAllTranslationByLanguage(
        string $translationFor,
        string $keyLanguage,
        bool $replaceNonExistentOnEn
    ): array {
        $allTrans = $this->translationRepository->getAllTranslationByLanguageIndexedByPosition(
            $translationFor,
            $keyLanguage
        );

        if (!$replaceNonExistentOnEn || User::DEFAULT_LOCALE === $keyLanguage) {
            return $allTrans;
        }

        $enTranslations = $this->translationRepository->getAllTranslationByLanguageIndexedByPosition(
            $translationFor,
            User::DEFAULT_LOCALE
        );

        foreach ($enTranslations as $key => $enTranslation) {
            if (!array_key_exists($key, $allTrans)) {
                $allTrans[$key] = $enTranslation;
            }
        }

        ksort($allTrans);

        return $allTrans;
    }

    public function findTranslationBy(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation
    ): ?Translation {
        return $this->translationRepository->findTranslationBy(
            $translationFor,
            $keyLanguage,
            $keyTranslation
        );
    }
}
