<?php declare(strict_types = 1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateOutdatedAirdropsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:update-outdated-airdrops';

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update outdated airdrops status.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $this->em->getConnection()->exec('
            UPDATE airdrop
            SET status = 0
            WHERE status = 1 AND end_date IS NOT NULL AND end_date < CURRENT_TIMESTAMP();');
        $io->success('Airdrops updated.');
    }
}
