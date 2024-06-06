<?php declare(strict_types = 1);

namespace App\Command\Translations;

use App\Entity\Translation;
use App\Manager\TranslationsManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateTranslationsCommand extends Command
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
            ->setName('app:create-translation')
            ->setDescription('Create new translation')
            ->setHelp('
                To generate a new translation use the following syntax:
                app:create-translation {position} {translation_for} {key_language} {key_translation} {content}
            ')
            ->addArgument('position', InputArgument::REQUIRED, 'Translation position')
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

        $position = (string)$input->getArgument('position');

        return $this->createTranslation(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
            $content,
            $position,
            $io
        );
    }

    private function createTranslation(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation,
        string $content,
        string $position,
        SymfonyStyle $io
    ): int {
        $translation = $this->translationsManager->findTranslationBy(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
        );

        if ($translation) {
            $io->error('Translation already exists');

            return 1;
        }

        $translation = new Translation(
            $translationFor,
            $keyLanguage,
            $keyTranslation,
            $content,
            $position,
        );

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        $io->success("{$keyTranslation} added successfully");

        return 0;
    }
}
