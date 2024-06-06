<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\DeployNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class DeployNotificationBotCommand extends Command
{
    private LoggerInterface $logger;
    private TokenManagerInterface $tokenManager;
    private DeployNotificationManagerInterface $deployNotificationManager;
    private UserManagerInterface $userManager;
    private int $notificationsLimit;
    private array $botsIds;

    public function __construct(
        LoggerInterface $logger,
        TokenManagerInterface $tokenManager,
        DeployNotificationManagerInterface $deployNotificationManager,
        UserManagerInterface $userManager,
        int $notificationsLimit,
        array $botsIds
    ) {
        $this->logger = $logger;
        $this->tokenManager = $tokenManager;
        $this->deployNotificationManager = $deployNotificationManager;
        $this->userManager = $userManager;

        parent::__construct();
        $this->notificationsLimit = $notificationsLimit;
        $this->botsIds = $botsIds;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:run-deploy-notification-bot')
            ->setDescription('Runs command to notify token owners to deploy tokens from users-bots');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxNotifications = $this->notificationsLimit;

        $bots = $this->getBotUsers();

        if (!$bots) {
            $emptyBotsMsg = 'No bots found';
            $io->error($emptyBotsMsg);
            $this->logger->error("[deploy-notification-bot] ${emptyBotsMsg}");

            return 1;
        }

        $botsCount = count($bots);

        if ($maxNotifications > $botsCount) {
            $botsNotEnough = "Max notification value: ${maxNotifications} can't be more than bots: ${botsCount}";
            $io->error($botsNotEnough);
            $this->logger->error("[deploy-notification-bot] ${$botsNotEnough}");

            return 1;
        }

        $this->logger->info('[deploy-notification-bot] Job has started');

        $tokenToNotify = $this->tokenManager->findNotNotifiedByUsersNotDeployedToken(
            $bots,
            $maxNotifications
        );

        if (!$tokenToNotify) {
            $noTokensMsg = "Db doesn't have tokens to notify";
            $io->warning($noTokensMsg);
            $this->logger->warning("[deploy-notification-bot] ${noTokensMsg}");

            return 1;
        }

        $botToNotify = $this->getUserBotToNotify($bots, $tokenToNotify);
        $botId = $botToNotify->getId();
        $tokenId = $tokenToNotify->getId();

        try {
            $this->deployNotificationManager->createAndNotify($botToNotify, $tokenToNotify);
        } catch (\Throwable $exception) {
            $exceptionMsg = $exception->getMessage();
            $logMsg = "Bot id: ${botId}, token id: ${tokenId}. Message: ${exceptionMsg}";
            $io->error($logMsg);
            $this->logger->error("[deploy-notification-bot] ${logMsg}");

            throw $exception;
        }

        $successMsg = "Token with id ${tokenId} was notified by bot with id ${botId}";
        $io->success($successMsg);
        $this->logger->info($successMsg);

        return 0;
    }

    /**
     * @param User[] $bots
     */
    private function getUserBotToNotify(array $bots, Token $token): User
    {
        $notNotifiedBots = array_filter(
            $bots,
            fn (User $bot) => !$this->deployNotificationManager->findByUserAndToken($bot, $token)
        );

        $randomKey = array_rand($notNotifiedBots);

        return $notNotifiedBots[$randomKey];
    }

    /**
     * @return User[]
     * @throws \Exception
     */
    private function getBotUsers(): array
    {
        $botUsers = [];

        foreach ($this->botsIds as $botId) {
            $userBot = $this->userManager->find((int)$botId);

            if ($userBot) {
                $botUsers[] = $userBot;
            }
        }

        return $botUsers;
    }
}
