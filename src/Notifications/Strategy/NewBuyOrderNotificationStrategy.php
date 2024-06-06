<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;

class NewBuyOrderNotificationStrategy implements NotificationStrategyInterface
{
    private Profile $investor;
    private Market $market;
    private string $type;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;

    public function __construct(
        Profile $investor,
        Market $market,
        string $type,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer
    ) {
        $this->investor = $investor;
        $this->market = $market;
        $this->type = $type;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
    }

    public function sendNotification(User $user): void
    {
        /** @var Token $token */
        $token = $this->market->getQuote();
        /** @var Crypto $crypto */
        $crypto = $this->market->getBase();

        $jsonData = (array)json_encode(
            [
                'nickname' => $this->investor->getNickname(),
                'tokenName' => $token->getName(),
                'crypto' => $crypto->getSymbol(),
            ],
            JSON_THROW_ON_ERROR
        );

        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);

        $this->mailer->sendNewBuyOrderMail($user, $this->investor->getUser(), $token->getName(), $crypto->getSymbol());
    }
}
