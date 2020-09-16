<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\AirdropCampaignManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateOutdatedAirdropsCommand extends Command
{

    use LockTrait;

    /** @var string */
    protected static $defaultName = 'app:update-outdated-airdrops';

    /** @var AirdropCampaignManagerInterface */
    protected $manager;

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(
        AirdropCampaignManagerInterface $manager,
        EntityManagerInterface $em
    ) {
        $this->manager = $manager;

        $this->em = $em;

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
        $lock = $this->createLock($this->em->getConnection(), 'update-outdated-airdrops');

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
