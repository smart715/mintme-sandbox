<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\PostUserShareRewardRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MoveTokenBalanceToBonusBalanceCommand extends Command
{
    private TokenManagerInterface $tokenManager;
    private UserManagerInterface $userManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private AirdropParticipantRepository $airdropParticipantRepository;
    private PostUserShareRewardRepository $postUserShareRewardRepository;

    public function __construct(
        TokenManagerInterface $tokenManager,
        UserManagerInterface $userManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        AirdropParticipantRepository $airdropParticipantRepository,
        PostUserShareRewardRepository $postUserShareRewardRepository
    ) {
        $this->tokenManager = $tokenManager;
        $this->userManager = $userManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->airdropParticipantRepository = $airdropParticipantRepository;
        $this->postUserShareRewardRepository = $postUserShareRewardRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:move-token-balance-to-bonus')
            ->addArgument('email', InputArgument::REQUIRED, 'Email of user')
            ->addArgument('token', InputArgument::OPTIONAL, 'Token name')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force move without any checks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $tokenName = $input->getArgument('token');
        $force = (bool)$input->getOption('force');

        if (!is_string($email)) {
            $io->error('Wrong email argument, it must be a string!');

            return 1;
        }

        /** @var User|null $user */
        $user = $this->userManager->findUserByEmail($email);

        if (!$user) {
            $io->error(sprintf('User with email \'%s\' not found!', $email));

            return 1;
        }

        if ($tokenName) {
            if (!is_string($tokenName)) {
                $io->error('Wrong token name argument, it must be a string!');

                return 1;
            }

            $token = $this->tokenManager->findByName($tokenName);

            if (!$token) {
                $io->error(sprintf('Token \'%s\' not found!', $tokenName));

                return 1;
            }

            $this->moveBalanceToBonus($io, $user, $token, $force);
        } else {
            $tokens = $user->getTokens();
            $movedBalances = 0;

            foreach ($tokens as $token) {
                if ($this->moveBalanceToBonus($io, $user, $token, $force)) {
                    $movedBalances++;
                }
            }

            $io->success(sprintf(
                'Successfully moved balance of %s of %s tokens of user %s',
                $movedBalances,
                count($tokens),
                $email,
            ));
        }

        return 0;
    }

    private function moveBalanceToBonus(SymfonyStyle $io, User $user, Token $token, bool $force): bool
    {
        if (!$force && !$this->hasClaimedPromotionTokens($user, $token)) {
            $io->warning(sprintf(
                'Token \'%s\' :: User doesn\'t have tokens earned from airdrops or share post rewards. Nothing will be moved',
                $token->getName(),
            ));

            return false;
        }

        $balance = $this->balanceHandler->exchangeBalance($user, $token);

        if ($balance->isZero()) {
            $io->warning(sprintf('Token \'%s\' :: Balance is 0. Nothing will be moved', $token->getName()));

            return false;
        }

        try {
            $this->balanceHandler->beginTransaction();

            $this->balanceHandler->withdraw($user, $token, $balance);
            $this->balanceHandler->depositBonus(
                $user,
                $token,
                $balance,
                BalanceTransactionBonusType::MOVE_MAIN_BALANCE
            );
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            $io->error(sprintf('Something went wrong: \'%s\'', $e->getMessage()));

            return false;
        }

        $io->success(sprintf(
            '\'%s\' :: balance was successfully moved to bonus balance. Moved balance: %s',
            $token->getName(),
            $this->moneyWrapper->format($balance)
        ));

        return true;
    }

    private function hasClaimedPromotionTokens(User $user, Token $token): bool
    {
        return $this->postUserShareRewardRepository->hasSharePostReward($user, $token)
            || $this->airdropParticipantRepository->hasAirdropReward($user, $token);
    }
}
