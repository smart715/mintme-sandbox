<?php declare(strict_types = 1);

namespace App\Command;

use App\Config\BlacklistIpConfig;
use App\Entity\Blacklist\BlacklistIp;
use App\Manager\BlacklistIpManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveBlacklistIp extends Command
{
    private BlacklistIpManagerInterface $blacklistIpManager;
    private BlacklistIpConfig $blacklistIpConfig;

    public function __construct(
        BlacklistIpManagerInterface $blacklistIpManager,
        BlacklistIpConfig $blacklistIpConfig
    ) {
        $this->blacklistIpManager = $blacklistIpManager;
        $this->blacklistIpConfig = $blacklistIpConfig;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:remove-blacklisted-ip')
            ->setDescription('Remove Blacklisted Ip from database')
            ->addArgument('address', InputArgument::OPTIONAL, 'Ip address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $address */
        $address = $input->getArgument('address');

        if (null !== $address && !$this->isIpAddress($address)) {
            $io->error('IP address is not valid');

            return 1;
        }

        if (null !== $address) {
            /** @var BlacklistIp|null $blackListIp */
            $blackListIp = $this->blacklistIpManager->getBlackListIpByAddress((string)$address);

            if (!$blackListIp) {
                $io->error('IP address not found');

                return 1;
            }

            $this->blacklistIpManager->deleteBlacklistIp($blackListIp);
            $io->success('IP address "' . $address . '" has been deleted');

            return 0;
        }

        $queryBuilder = $this->blacklistIpManager
            ->getBlackListIpByNumberOfDaysQueryBuilder($this->blacklistIpConfig->getDays());

        $iterable = $queryBuilder->getQuery()->iterate();
        $count = 0;

        foreach ($iterable as $blackListIp) {
            $this->blacklistIpManager->deleteBlacklistIp($blackListIp);
            ++$count;
            $this->consoleWriteLine($output, $blackListIp->getAddress());
        }

         $io->success($count . ' IP(s) have been deleted.');

        return 0;
    }

    private function consoleWriteLine(OutputInterface $output, string $address): void
    {
        $output->writeln('IP address ' . $address . ' has been deleted');
    }

    private function isIpAddress(string $ipAddress): bool
    {
        return (bool)filter_var($ipAddress, FILTER_VALIDATE_IP);
    }
}
