<?php declare(strict_types = 1);

namespace App\Command\Translations;

use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateTranslationsCommand extends Command
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
            ->setName('app:update-translation')
            ->setDescription('Update translation')
            ->setHelp('
                To update a new translation use the following syntax:
                app:update-translation {translation_for} {key_language} {key_translation} {content}
            ')
            ->addArgument('translation_for', InputArgument::REQUIRED, 'Translation for')
            ->addArgument('key_language', InputArgument::REQUIRED, 'Key of language')
            ->addArgument('key_translation', InputArgument::REQUIRED, 'Key of translation')
            ->addArgument('content', InputArgument::OPTIONAL, 'Translation content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $translationFor = (string)$input->getArgument('translation_for');

        $keyLanguage = (string)$input->getArgument('key_language');

        $keyTranslation = (string)$input->getArgument('key_translation');

        $content = (string)$input->getArgument('content');

        return $this->updateTranslation(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
            $content,
            $io
        );
    }

    private function updateTranslation(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation,
        string $content,
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

        $translation->setContent($content);

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        $io->success("{$keyTranslation} has been successfully updated");

        return 0;
    }
}
