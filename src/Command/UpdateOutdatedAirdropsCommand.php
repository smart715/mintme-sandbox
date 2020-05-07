<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\AirdropCampaign\Airdrop;
use App\Repository\AirdropCampaign\AirdropRepository;
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
        $this->setDescription('Update outdated airdrops status.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        /** @var AirdropRepository $repository */
        $repository = $this->em->getRepository(Airdrop::class);
        $repository->updateOutdatedAirdrops();
        $io->success('Airdrops updated.');
    }
}
