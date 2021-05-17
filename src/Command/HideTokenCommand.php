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

class HideTokenCommand extends Command
{
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
            ->setName('app:hide-token')
            ->setDescription('Hide a token from the trading list')
            ->addArgument('name', InputArgument::REQUIRED, 'Token name you wish to hide')
            ->addOption('unhide', 'u', InputOption::VALUE_NONE, 'If you want to unhide a token instead')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Wrong token name argument, it must be a string!');

            return 1;
        }

        $token = $this->tokenManager->findByName($name);
        $unhide = (bool)$input->getOption('unhide');

        if (!$token) {
            $io->error(sprintf('Token \'%s\' not found!', $name));

            return 1;
        }
        
        if ($token->isHidden() && !$unhide) {
            $io->error(sprintf('Token \'%s\' is already hidden. Use --unhide flag to unhide the token.', $name));

            return 1;
        }

        if (!$token->isHidden() && $unhide) {
            $io->error(sprintf('Token \'%s\' is not hidden. Remove the --unhide flag to hide the token.', $name));

            return 1;
        }

        $token->setIsHidden(!$unhide);

        $this->em->persist($token);
        $this->em->flush();

        $io->success(sprintf('Token \'%s\' was successfully %s.', $name, $unhide ? 'unhidden' : 'hidden'));

        return 0;
    }
}
