<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetUserCustomTradingFee extends Command
{
    private EntityManagerInterface $em;
    private UserManagerInterface $userManager;
    
    public function __construct(EntityManagerInterface $em, UserManagerInterface $userManager)
    {
        $this->em = $em;
        $this->userManager = $userManager;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setName('app:custom-fee')
            ->setHelp('Use --reset-to-default to reset custom fee to default')
            ->setDescription('add/edit/delete custom trading fee for the user')
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption(
                'reset-to-default',
                null,
                InputOption::VALUE_NONE,
                'Use it to reset custom fee to default'
            )
            ->addOption(
                'fee',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Custom fee for the user'
            )
        ;
    }
    
    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $fee = strval($input->getOption('fee'));
        $username = $input->getArgument('username');
        $reset = (bool)$input->getOption('reset-to-default');
        
        if (!is_string($username)) {
            $io->error('Wrong username name argument');
            
            return 1;
        }
        
        /** @var User|null */
        $user = $this->userManager->findUserByEmail($username);
        
        if (!$user) {
            $io->error('User doesn\'t exist');
            
            return 1;
        }
        
        if (!is_numeric($fee) && !$reset) {
            $io->error('Wrong fee value, please check');
    
            return 1;
        }
        
        if (($fee < 0 || $fee >= 1) && !$reset) {
            $io->error('Fee value should be between 0 and slightly less than 1 (~100%)');
    
            return 1;
        }
        
        $user->setTradingFee($reset ? null : $fee);
        
        $this->em->persist($user);
        $this->em->flush();
        
    
        $io->success('Custom maker/taker fee was set in '. ($reset ? 'default' : $fee));
        
        return 0;
    }
}
