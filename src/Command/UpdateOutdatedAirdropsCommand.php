<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\AirdropCampaignManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateOutdatedAirdropsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:update-outdated-airdrops';

    /** @var AirdropCampaignManagerInterface */
    protected $manager;

    public function __construct(AirdropCampaignManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Update outdated airdrops status.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $countUpdated = $this->manager->updateOutdatedAirdrops();
        $io->success($countUpdated . ' airdrops updated.');

        return 0;
    }
}
