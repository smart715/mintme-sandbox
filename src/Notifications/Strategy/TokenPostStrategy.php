<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Entity\UserToken;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use Doctrine\ORM\EntityManagerInterface;

class TokenPostStrategy implements NotificationStrategyInterface
{
    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    private string $type;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        EntityManagerInterface $em,
        string $type
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->type = $type;
    }

    public function notification(User $user): void
    {
        foreach ($this->getUsersHaveTokenIds($user) as $userHaveToken) {
            $token = $user->getProfile()->getToken();
            $userWithToken = $this->em->getRepository(User::class)->find($userHaveToken);

            $tokenName = $token->getName();
            $jsonData = (array)json_encode([
                'tokenName' => $tokenName,
            ], JSON_THROW_ON_ERROR);

            if ($this->userNotificationManager->isNotificationAvailable(
                $userWithToken,
                $this->type,
                NotificationChannels::WEBSITE
            )
            ) {
                $this->userNotificationManager->createNotification($userWithToken, $this->type, $jsonData);
            }

            if ($this->userNotificationManager->isNotificationAvailable(
                $userWithToken,
                $this->type,
                NotificationChannels::EMAIL
            )
            ) {
                    $this->mailer->sendNewPostMail($userWithToken, $tokenName);
            }
        }
    }
    private function getUsersHaveTokenIds(User $user): array
    {
        $result = [];
        $usersTokens = $this->em->getRepository(UserToken::class)->findAll();

        if (!$usersTokens) {
            return $result;
        }

        foreach ($usersTokens as $userToken) {
            $tokenId = $user->getProfile()->getToken()->getId();
            $userId = $user->getId();
            $userWithToken = $userToken->getuser()->getId();
            $userTokenId = $userToken->getToken()->getId();

            if ($userTokenId === $tokenId && $userWithToken !== $userId) {
                $result[] = $userWithToken;
            }
        }

        return $result;
    }
}
