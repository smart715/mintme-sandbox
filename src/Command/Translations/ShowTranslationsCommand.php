<?php declare(strict_types = 1);

namespace App\Command\Translations;

use App\Entity\Translation;
use App\Manager\TranslationsManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowTranslationsCommand extends Command
{
    private TranslationsManagerInterface $translationsManager;

    public function __construct(
        TranslationsManagerInterface $translationsManager
    ) {
        $this->translationsManager = $translationsManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:show-translation')
            ->setDescription('Update translation')
            ->setHelp('
                To display the translations, use the following syntax:
                app:show-translation {translation_for} {key_language} {key_translation}:optional
            ')
            ->addArgument('translation_for', InputArgument::REQUIRED, 'Translation for')
            ->addArgument('key_language', InputArgument::REQUIRED, 'Key of language')
            ->addArgument('key_translation', InputArgument::OPTIONAL, 'Key of translation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $io = new SymfonyStyle($input, $output);

        $translationFor = (string)$input->getArgument('translation_for');

        $keyLanguage = (string)$input->getArgument('key_language');

        $keyTranslation = (string)$input->getArgument('key_translation');

        return $this->showTranslation(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
            $table,
            $io
        );
    }

    private function showTranslation(
        string $translationFor,
        string $keyLanguage,
        ?string $keyTranslation,
        Table $table,
        SymfonyStyle $io
    ): int {
        $translations = $keyTranslation
            ? [$this->translationsManager->findTranslationBy($translationFor, $keyLanguage, $keyTranslation)]
            : $this->translationsManager->getAllTranslationByLanguage($translationFor, $keyLanguage, false);

        if (!$translations || in_array(null, $translations)) {
            $io->error('No translations');

            return 1;
        }

        $rows = $this->generateRows($translations, $keyTranslation);

        $table->setHeaders([
            'position',
            'translation_for',
            'key_language',
            'key_translation',
            'content',
        ])->setRows($rows);

        $table->setColumnMaxWidth(4, 80);
        $table->render();

        return 0;
    }

    private function generateRows(array $translations, string $keyTranslation): array
    {
        if ($keyTranslation) {
            return [[
                $translations[0]->getPosition(),
                $translations[0]->getTranslationFor(),
                $translations[0]->getKeyLanguage(),
                $translations[0]->getKeyTranslation(),
                $translations[0]->getContent(),
            ]];
        }

        return array_map(static fn(Translation $translation) => [
            $translation->getPosition(),
            $translation->getTranslationFor(),
            $translation->getKeyLanguage(),
            $translation->getKeyTranslation(),
            $translation->getContent(),
        ], $translations);
    }
}
