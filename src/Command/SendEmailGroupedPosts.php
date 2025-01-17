<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Utils\Policy\NotificationPolicyInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendEmailGroupedPosts extends Command
{
    private MailerInterface $mail;
    private PostManagerInterface $postManager;
    private TokenManagerInterface $tokenManager;
    private UserNotificationManagerInterface $userNotificationManager;
    private NotificationPolicyInterface $notificationPolicy;

    public function __construct(
        MailerInterface $mail,
        PostManagerInterface $postManager,
        TokenManagerInterface $tokenManager,
        UserNotificationManagerInterface $userNotificationManager,
        NotificationPolicyInterface $notificationPolicy
    ) {
        $this->postManager = $postManager;
        $this->tokenManager = $tokenManager;
        $this->mail = $mail;
        $this->userNotificationManager = $userNotificationManager;
        $this->notificationPolicy = $notificationPolicy;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-grouped-posts')
            ->setDescription('Send grouped posts to users')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $date */
        $date = $input->getArgument('date');

        if ($date) {
            if (!is_string($date)) {
                $io->error('Wrong date argument');

                return 1;
            }

            if (!$this->validateDate($date)) {
                $output->writeln("$date: is not a valid date");

                return 1;
            }
        }

        /** @var \DateTimeImmutable $dateTimeImmutable */
        $dateTimeImmutable = $date
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $date)
            : new \DateTimeImmutable('-1 day');

        $posts = $this->postManager->getPostsCreatedAt($dateTimeImmutable);

        $data = [];

        foreach ($posts as $post) {
            $data[$post->getToken()->getName()][] = $post;
        }

        foreach ($data as $tokenName => $groupedPosts) {
            $token = $this->tokenManager->findByName((string)$tokenName);
            $users = $token->getUsers();
            $groupedPostsCount = count($groupedPosts);

            foreach ($users as $user) {
                if ($this->isNotificationAvailable($user)
                    && $user->getId() !== $token->getOwner()->getId()
                    && 2 < $groupedPostsCount
                    && $this->notificationPolicy->canReceiveNotification($user, $token)
                ) {
                    array_pop($groupedPosts);
                    $this->mail->sendGroupedPosts($user, (string)$tokenName, $groupedPosts);
                }
            }
        }

        $output->writeln('Emails has been sent');

        return 0;
    }

    private function isNotificationAvailable(User $user): bool
    {
        return $this->userNotificationManager->isNotificationAvailable(
            $user,
            NotificationTypes::TOKEN_NEW_POST,
            NotificationChannels::EMAIL
        );
    }

    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateObject = \DateTime::createFromFormat($format, $date);

        return $dateObject && $dateObject->format($format) === $date;
    }
}
