<?php declare(strict_types = 1);

namespace App\Command;

use App\Repository\VotingRepository;
use App\Utils\Converter\SlugConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateVotingSlugsCommand extends Command
{
    private VotingRepository $votingRepository;
    private EntityManagerInterface $entityManager;
    private SlugConverterInterface $slugger;

    public function __construct(
        VotingRepository $votingRepository,
        EntityManagerInterface $entityManager,
        SlugConverterInterface $slugger
    ) {
        $this->votingRepository = $votingRepository;
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-voting-slugs')
            ->setDescription('Update voting slugs that have NULL value')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $votings = $this->votingRepository->findBy(['slug' => null]);

        try {
            $this->entityManager->beginTransaction();

            foreach ($votings as $voting) {
                $slug = $this->slugger->convert($voting->getTitle(), $this->votingRepository);

                $voting->setSlug($slug);

                $this->entityManager->persist($voting);
                $this->entityManager->flush();
            }

            $this->entityManager->commit();
        } catch (\Throwable $ex) {
            $io->error("Something went wrong, aborting...");
            $this->entityManager->rollback();

            throw $ex;
        }

        $count = count($votings);
        $io->success("We updated {$count} votings susscessfully.");

        return 0;
    }
}
