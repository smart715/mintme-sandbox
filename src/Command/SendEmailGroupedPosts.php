<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Post;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailGroupedPosts extends Command
{
    private MailerInterface $mail;
    private PostManagerInterface $postManager;
    private TokenManagerInterface $tokenManager;
    private UserNotificationManagerInterface $userNotificationManager;

    public function __construct(
        MailerInterface $mail,
        PostManagerInterface $postManager,
        TokenManagerInterface $tokenManager,
        UserNotificationManagerInterface $userNotificationManager
    ) {
        $this->postManager = $postManager;
        $this->tokenManager = $tokenManager;
        $this->mail = $mail;
        $this->userNotificationManager = $userNotificationManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-grouped-posts')
            ->setDescription('Send grouped posts to users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $posts = $this->postManager->getPostsCreatedToday();

        $data = [];

        /** @var Post $post */
        foreach ($posts as $post) {
            $data[$post->getToken()->getName()][] = $post;
        }

        foreach ($data as $tokenName => $groupedPosts) {
            $token = $this->tokenManager->findByName($tokenName);
            $users = $token->getUsers();
            $groupedPostsCount = count($groupedPosts);

            foreach ($users as $user) {
                if ($this->userNotificationManager->isNotificationAvailable(
                    $user,
                    NotificationTypes::TOKEN_NEW_POST,
                    NotificationChannels::EMAIL
                )
                ) {
                    if ($user->getEmail() !== $token->getOwner()->getEmail()) {
                        if (2 < $groupedPostsCount) {
                            array_pop($groupedPosts);
                            $this->mail->sendGroupedPosts($user, $tokenName, $groupedPosts);
                        }
                    }
                }
            }
        }

        $output->writeln('Emails has been sent');

        return 0;
    }
}
