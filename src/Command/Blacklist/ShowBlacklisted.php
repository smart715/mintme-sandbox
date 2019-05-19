<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowBlacklisted extends Command
{
    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    public function __construct(BlacklistManagerInterface $blacklistManager)
    {
        $this->blacklistManager = $blacklistManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blacklist:show')
            ->setDescription('Shows blacklisted')
            ->addArgument('type', InputArgument::OPTIONAL, 'Blacklist type, e.g. "token"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var string|null $type */
        $type = $input->getArgument('type');

        $style = new SymfonyStyle($input, $output);

        $list = $this->blacklistManager->getList($type);

        if (!$list) {
            $style->success("No entries found");

            return;
        }

        $style->table(['type', 'value'], array_map(function (Blacklist $blacklist) {
            return [$blacklist->getType(), $blacklist->getValue()];
        }, $list));
    }
}
