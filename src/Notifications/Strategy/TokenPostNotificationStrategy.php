<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class TokenPostNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private Token $token;
    private string $type;
    private array $extraData;
    private PostManagerInterface $postManager;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        Token $token,
        array $extraData,
        string $type,
        PostManagerInterface $postManager
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->token = $token;
        $this->type = $type;
        $this->extraData = $extraData;
        $this->postManager = $postManager;
    }

    public function sendNotification(User $user): void
    {
        $tokenName = $this->token->getName();
        $data = array_merge(['tokenName' => $tokenName], $this->extraData);
        $jsonData = (array)json_encode($data, JSON_THROW_ON_ERROR);

        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::WEBSITE
        )
        ) {
            $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        }

        $datetime = date('Y-m-d');
        $dateTimeImmutable = \DateTimeImmutable::createFromFormat('Y-m-d', $datetime);
        $posts = $this->postManager->getPostsCreatedAtByToken($this->token, $dateTimeImmutable);

        if (1 < count($posts)) {
            return;
        }

        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::EMAIL
        )
        ) {
            $this->mailer->sendNewPostMail($user, $tokenName, $this->extraData['slug']);
        }
    }
}
