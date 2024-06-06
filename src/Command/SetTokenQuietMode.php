<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetTokenQuietMode extends Command
{
    private TokenManagerInterface $tokenManager;
    private EntityManagerInterface $em;
    private UserActionLogger $logger;
    
    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $em,
        UserActionLogger $logger
    ) {
        $this->tokenManager = $tokenManager;
        $this->em = $em;
        $this->logger = $logger;
        
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->setName('app:quiet-mode')
            ->setDescription('use it for tokens that spam or use inappropriate language.')
            ->addArgument('name', InputArgument::REQUIRED, 'Token name,
             if token name contain spaces you should place parameter in quotes')
            ->addOption('verbose-mode', null, InputOption::VALUE_NONE, 'Use it to remove quiet mode for  token')
        ;
    }
    
    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $verbose = (bool)$input->getOption('verbose-mode');
        $name = $input->getArgument('name');
        
        if (!is_string($name)) {
            $io->error('Wrong token name argument');
            
            return 1;
        }
        
        /** @var Token|null $token*/
        $token = $this->tokenManager->findByName($name);
        
        if (!$token) {
            $io->warning('Token doesn\'t exist');
            
            return 1;
        }
        
        $tokenQuiet = $token->isQuiet();
        
        if ($tokenQuiet && !$verbose) {
            $io->warning('Token is already in quiet mode');
    
            return 1;
        }
    
        $token->setIsQuiet(!$verbose);
        
        $this->em->persist($token);
        $this->em->flush();
        
        $message = 'Token '.$token->getName(). ' was '.($verbose ? 'verbose mode' : 'quiet mode');
        $this->logger->info($message);
        $io->success($message);
        
        return 0;
    }
}
