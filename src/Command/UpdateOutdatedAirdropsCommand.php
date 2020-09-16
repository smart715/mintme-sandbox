<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\AirdropCampaignManagerInterface;
use App\Utils\LockFactory;
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

    /** @var LockFactory */
    protected $lockFactory;

    public function __construct(
        AirdropCampaignManagerInterface $manager,
        LockFactory $lockFactory
    ) {
        $this->manager = $manager;
        $this->lockFactory = $lockFactory;

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
        $lock = $this->lockFactory->createLock('update-outdated-airdrops');

        if (!$lock->acquire()) {
            return 0;
        }

        $io = new SymfonyStyle($input, $output);
        $countUpdated = $this->manager->updateOutdatedAirdrops();
        $io->success($countUpdated . ' airdrops updated.');

        $lock->release();

        return 0;
    }
}
