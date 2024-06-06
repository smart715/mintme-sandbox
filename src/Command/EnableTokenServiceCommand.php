<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnableTokenServiceCommand extends Command
{
    private const DEPOSITS_OPT = 'deposits';
    private const WITHDRAWALS_OPT = 'withdrawals';
    private const TRADES_OPT = 'trades';

    private TokenManagerInterface $tokenManager;
    private EntityManagerInterface $em;

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $em
    ) {
        $this->tokenManager = $tokenManager;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('token:set-service-enabled')
            ->addArgument('token', InputArgument::REQUIRED, 'Token name')
            ->addOption(self::DEPOSITS_OPT, null, InputOption::VALUE_REQUIRED, '"yes" to allow deposits, "no" to disable deposits')
            ->addOption(self::WITHDRAWALS_OPT, null, InputOption::VALUE_REQUIRED, '"yes" to allow withdrawals, "no" to disable withdrawals')
            ->addOption(self::TRADES_OPT, null, InputOption::VALUE_REQUIRED, '"yes" to allow trades, "no" to disable trades');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tokenName = $input->getArgument('token');
        /** @var string|null $deposits */
        $deposits = $input->getOption(self::DEPOSITS_OPT);
        /** @var string|null $withdrawals */
        $withdrawals = $input->getOption(self::WITHDRAWALS_OPT);
        /** @var string|null $trades */
        $trades = $input->getOption(self::TRADES_OPT);

        if (!is_string($tokenName)) {
            $io->error('Wrong token name argument, it must be a string!');

            return 1;
        }

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            $io->error(sprintf('Token \'%s\' not found!', $tokenName));

            return 1;
        }

        if (!$deposits && !$withdrawals && !$trades) {
            $io->error('You didnt pick any service to change! Nothing will be changed');

            return 1;
        }

        try {
            if ($deposits) {
                $token->setDepositsDisabled(!$this->convertYesNoToBoolean($deposits));
            } elseif ($withdrawals) {
                $token->setWithdrawalsDisabled(!$this->convertYesNoToBoolean($withdrawals));
            } elseif ($trades) {
                $token->setTradesDisabled(!$this->convertYesNoToBoolean($trades));
            } else {
                $io->error('No input specified');
    
                return 1;
            }
        } catch (\Throwable $e) {
            $io->error('Wrong input');

            return 1;
        }

        $this->em->persist($token);
        $this->em->flush();

        $io->success("{$tokenName} service updated successfully");

        return 0;
    }

    private function convertYesNoToBoolean(string $value): bool
    {
        if (null == $value || !in_array($value, ["yes", "no"])) {
            throw new \InvalidArgumentException('Wrong input');
        }

        return "yes" === $value;
    }
}
