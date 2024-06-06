<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\Policy\NotificationPolicyInterface;

class TokenPostNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;
    private Token $token;
    private string $type;
    private array $extraData;
    private NotificationPolicyInterface $notificationPolicy;
    private MailerInterface $mailer;
    private PostManagerInterface $postManager;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        Token $token,
        array $extraData,
        string $type,
        NotificationPolicyInterface $notificationPolicy,
        MailerInterface $mailer,
        PostManagerInterface $postManager
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->token = $token;
        $this->type = $type;
        $this->extraData = $extraData;
        $this->notificationPolicy = $notificationPolicy;
        $this->mailer = $mailer;
        $this->postManager = $postManager;
    }

    public function sendNotification(User $user): void
    {
        $tokenName = $this->token->getName();
        $tokenAvatar = $this->token->getImage()->getUrl();
        $data = array_merge(['tokenName' => $tokenName, 'tokenAvatar' => $tokenAvatar], $this->extraData);
        $jsonData = (array)json_encode($data, JSON_THROW_ON_ERROR);

        $canReceiveNotification = $this->notificationPolicy->canReceiveNotification($user, $this->token);
        $isNotificationAvailable = $this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::WEBSITE
        );

        if ($this->token->isQuiet() || !$canReceiveNotification || !$isNotificationAvailable) {
            return;
        }

        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        $posts = $this->postManager->getPostsCreatedAtByToken($this->token, (new \DateTimeImmutable()), true);

        if (1 !== count($posts)) {
            return;
        }

        $post = $posts[0];
        $this->mailer->sendNewPostMail($user, $tokenName, $post->getTitle(), $post->getSlug());
    }
}
