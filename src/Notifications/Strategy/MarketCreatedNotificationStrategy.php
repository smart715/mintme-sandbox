<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;

class MarketCreatedNotificationStrategy implements NotificationStrategyInterface
{
    private TokenCrypto $tokenCrypto;
    private string $type;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;

    public function __construct(
        TokenCrypto $tokenCrypto,
        string $type,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer
    ) {
        $this->tokenCrypto = $tokenCrypto;
        $this->type = $type;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
    }

    public function sendNotification(User $user): void
    {
        $tokenName = $this->tokenCrypto->getToken()->getName();
        $cryptoSymbol = $this->tokenCrypto->getCrypto()->getSymbol();
        $tokenAvatar = $this->tokenCrypto->getToken()->getImage()->getUrl();
        $cryptoAvatar = $this->tokenCrypto->getCrypto()->getImage()->getUrl();
        $this->userNotificationManager->createNotification($user, $this->type, (array)json_encode([
            'tokenName' => $tokenName,
            'cryptoSymbol' => $cryptoSymbol,
            'tokenAvatar' => $tokenAvatar,
            'cryptoAvatar' => $cryptoAvatar,
        ]));

        $this->mailer->sendMarketCreatedMail($user, $tokenName, $cryptoSymbol);
    }
}
