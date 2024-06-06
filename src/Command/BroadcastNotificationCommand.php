<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\BroadcastNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BroadcastNotificationCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:broadcast-notification')
            ->setDescription('Notify user with old addresses and delete the addresses')
            ->addArgument(
                'enContent',
                InputArgument::REQUIRED,
                'Content in English'
            )->addOption(
                'esContent',
                'es',
                InputOption::VALUE_OPTIONAL,
                'Content in Spanish, fallback to English if not provided'
            )->addOption(
                'frContent',
                'fr',
                InputOption::VALUE_OPTIONAL,
                'Content in French, fallback to English if not provided'
            )->addOption(
                'deContent',
                'de',
                InputOption::VALUE_OPTIONAL,
                'Content in German, fallback to English if not provided'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $enContent */
        $enContent = $input->getArgument('enContent');
        /** @var string $esContent */
        $esContent = $input->getOption('esContent') ?? $enContent;
        /** @var string $frContent */
        $frContent = $input->getOption('frContent') ?? $enContent;
        /** @var string $deContent */
        $deContent = $input->getOption('deContent') ?? $enContent;

        $notification = new BroadcastNotification(
            $enContent,
            $esContent,
            $frContent,
            $deContent
        );

        $this->em->persist($notification);
        $this->em->flush();

        $io->success('Done');

        return 0;
    }
}
