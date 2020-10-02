<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddToBlacklist extends Command
{
    public const CAN_ADD_MANUALLY_TYPES = [
        Blacklist::TOKEN,
        Blacklist::EMAIL,
    ];

    private BlacklistManagerInterface $blacklistManager;

    public function __construct(BlacklistManagerInterface $blacklistManager)
    {
        $this->blacklistManager = $blacklistManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blacklist:add')
            ->setDescription('Add row to blacklisted')
            ->addArgument('type', InputArgument::REQUIRED, 'Blacklist type')
            ->addArgument('value', InputArgument::REQUIRED, 'Value to be blocked');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string */
        $type = $input->getArgument('type');

        if (!in_array($type, self::CAN_ADD_MANUALLY_TYPES, true)) {
            $io->error(
                'Not supported type: '. $type.
                ', supported types are: '. implode(' ', self::CAN_ADD_MANUALLY_TYPES)
            );

            return 1;
        }

        /** @var string */
        $value = $input->getArgument('value');

        $this->blacklistManager->add($value, $type);
        $io->success("Added successfully");

        return 0;
    }
}
