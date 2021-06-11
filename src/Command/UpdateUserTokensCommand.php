<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\UserTokenRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateUserTokensCommand extends Command
{
    private UserTokenRepository $repository;
    private BalanceHandlerInterface $balanceHandler;

    public function __construct(
        UserTokenRepository $repository,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->repository = $repository;
        $this->balanceHandler = $balanceHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-user-tokens')
            ->setDescription('COMMAND FOR RELEASE, it updates user_tokens table on tokens with more than 2 holders.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $qb = $this->repository->createQueryBuilder('ut')
            ->groupBy('ut.token')
            ->having('COUNT(ut) > 1');

        $result = $qb->getQuery()->getResult();
        
        foreach ($result as $userToken) {
            $token = $userToken->getToken();

            foreach ($token->getUsers() as $user) {
                $this->balanceHandler->updateUserTokenRelation(
                    $user,
                    $token
                );
            }
        }

        $io->success('User tokens relation updated!');

        return 0;
    }
}
