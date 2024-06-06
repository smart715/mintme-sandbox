<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\TopHolderManagerInterface;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateTopHoldersCommand extends Command
{
    private TopHolderManagerInterface $topHolderManager;
    private EntityManagerInterface $entityManager;
    private TokenRepository $tokenRepository;

    private const BATCH_SIZE = 100;

    public function __construct(
        TopHolderManagerInterface $topHolderManager,
        EntityManagerInterface $entityManager,
        TokenRepository $tokenRepository
    ) {
        $this->topHolderManager = $topHolderManager;
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:top-holders:update')
            ->setDescription('Update top holders table with information from viabtc server.
                Top holders quantity for each token depends on `top_holder` parameter.
                Execute this command if you want to sync top holders or after change the parameter');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $tokens = $this
            ->tokenRepository
            ->createQueryBuilder('t')
            ->getQuery()
            ->iterate();

        $count = 0;

        foreach ($tokens as $token) {
            try {
                $this->topHolderManager->updateTopHolders($token);
            } catch (\Throwable $e) {
                $name = $token->getName();
                $reason = $e->getMessage();
                $io->writeln("Failed to update top holders for ${name} token. Reason: ${reason}");
            }

            if (0 === $count % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            ++$count;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success("Top Holders were updated from ${count} tokens");

        return 0;
    }
}
