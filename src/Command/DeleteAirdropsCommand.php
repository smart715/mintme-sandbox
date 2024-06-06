<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\AirdropCampaignManager;
use App\Repository\AirdropCampaign\AirdropRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteAirdropsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:delete-airdrops';

    private AirdropRepository $airdropRepository;
    private AirdropCampaignManager $airdropCampaignManager;

    public function __construct(AirdropRepository $airdropRepository, AirdropCampaignManager $airdropCampaignManager)
    {
        $this->airdropRepository = $airdropRepository;
        $this->airdropCampaignManager = $airdropCampaignManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deletes airdrops with a specific airdrop action type.')
            ->setHelp('This command deletes airdrops with a specific airdrop action type.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Retrieve and delete the airdrops who have only retweet action
        $airdrops = $this->airdropRepository->findBySingleActionType();

        foreach ($airdrops as $airdrop) {
            $this->airdropCampaignManager->deleteAirdrop($airdrop);
        }

        // Delete retweet action for the aidrops that have multiple actions
        $this->airdropRepository->deleteAirdropActions();

        $io->success('Airdrops deletion completed.');

        return 0;
    }
}
