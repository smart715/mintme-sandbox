<?php declare(strict_types = 1);

namespace App\Command;

use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\RewardRepository;
use App\Utils\Policy\NotificationPolicyInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendEmailGroupedBountiesRewardsCommand extends Command
{
    public const TYPE_ALL = 'all';
    public const TYPE_REWARD = 'reward';
    public const TYPE_BOUNTY = 'bounty';

    private const DEFAULT_TYPE = self::TYPE_ALL;

    private MailerInterface $mail;
    private RewardRepository $rewardRepository;
    private TokenManagerInterface $tokenManager;
    private NotificationPolicyInterface $notificationPolicy;

    public function __construct(
        MailerInterface $mail,
        RewardRepository $rewardRepository,
        TokenManagerInterface $tokenManager,
        NotificationPolicyInterface $notificationPolicy
    ) {
        $this->rewardRepository = $rewardRepository;
        $this->tokenManager = $tokenManager;
        $this->mail = $mail;
        $this->notificationPolicy = $notificationPolicy;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-grouped-rewards-bounties')
            ->setDescription('Send grouped rewards and bounties to users')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of reward', self::DEFAULT_TYPE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $date */
        $date = $input->getArgument('date');

        /** @var string $type */
        $type = $input->getArgument('type') ?? self::DEFAULT_TYPE;

        if ($date) {
            if (!is_string($date)) {
                $io->error('Wrong date argument');

                return 1;
            }

            if (!$this->validateDate($date)) {
                $output->writeln($date . ': is not a valid date');

                return 1;
            }
        }

        if (!is_string($type)) {
            $io->error('Wrong type argument');

            return 1;
        }

        if (!in_array($type, [self::TYPE_ALL, self::TYPE_BOUNTY, self::TYPE_REWARD])) {
            $output->writeln('type is wrong, can be all, bounty or reward');

            return 1;
        }

        /** @var \DateTimeImmutable $dateTimeImmutable */
        $dateTimeImmutable = $date
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $date)
            : new \DateTimeImmutable();

        if ("all" === $type) {
            $this->sendRewardsWithTypeAndDate("bounty", $dateTimeImmutable);
            $this->sendRewardsWithTypeAndDate("reward", $dateTimeImmutable);
            $output->writeln('Sending to bounty and reward');
        } else {
            $this->sendRewardsWithTypeAndDate($type, $dateTimeImmutable);
        }

        $output->writeln('Emails has been sent');

        return 0;
    }

    private function sendRewardsWithTypeAndDate(string $type, \DateTimeImmutable $dateTimeImmutable): void
    {
        $rewards = $this->rewardRepository->getRewardByCreatedAtDay(
            $type,
            $dateTimeImmutable
        );

        $data = [];

        foreach ($rewards as $reward) {
            $data[$reward->getToken()->getName()][] = $reward;
        }

        foreach ($data as $tokenName => $groupedRewards) {
            $token = $this->tokenManager->findByName((string)$tokenName);
            $users = $token->getUsers();
            $groupedRewardsCount = count($groupedRewards);

            foreach ($users as $user) {
                if ($user->getId() !== $token->getOwner()->getId()
                    && 2 < $groupedRewardsCount
                    && $this->notificationPolicy->canReceiveNotification($user, $token)
                ) {
                    array_pop($groupedRewards);
                    $this->mail->sendGroupedRewardsMail($user, (string)$tokenName, $groupedRewards, $type);
                }
            }
        }
    }

    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateObject = \DateTime::createFromFormat($format, $date);

        return $dateObject && $dateObject->format($format) === $date;
    }
}
