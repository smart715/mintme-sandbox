<?php declare(strict_types = 1);

namespace App\Command\Translations;

use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteTranslationsCommand extends Command
{
    private TranslationsManagerInterface $translationsManager;
    private EntityManagerInterface $entityManager;
    
    public function __construct(
        TranslationsManagerInterface $translationsManager,
        EntityManagerInterface $entityManager
    ) {
        $this->translationsManager = $translationsManager;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:delete-translation')
            ->setDescription('Update translation')
            ->setHelp('
                To remove a translation, use the following syntax:
                app:delete-translation {translation_for} {key_language} {key_translation}
            ')
            ->addArgument('translation_for', InputArgument::REQUIRED, 'Translation for')
            ->addArgument('key_language', InputArgument::REQUIRED, 'Key of language')
            ->addArgument('key_translation', InputArgument::REQUIRED, 'Key of translation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $translationFor = (string)$input->getArgument('translation_for');

        $keyLanguage = (string)$input->getArgument('key_language');

        $keyTranslation = (string)$input->getArgument('key_translation');

        return $this->deleteTranslation(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
            $io
        );
    }

    private function deleteTranslation(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation,
        SymfonyStyle $io
    ): int {
        $translation = $this->translationsManager->findTranslationBy(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
        );

        if (!$translation) {
            $io->error('The translation does not exist');

            return 1;
        }

        $this->entityManager->remove($translation);
        $this->entityManager->flush();

        $io->success("{$keyTranslation} has been successfully removed");

        return 0;
    }
}
