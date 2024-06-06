<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\ConnectCompletedEvent;
use App\Events\DeployCompletedEvent;
use App\Events\TokenEvent;
use App\Events\TokenEvents;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenDeployedNotificationStrategy;
use App\Utils\NotificationTypes;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeploySubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;

    private LoggerInterface $logger;

    private UserNotificationManagerInterface $userNotificationManager;
    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        UserNotificationManagerInterface $userNotificationManager,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->userNotificationManager = $userNotificationManager;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::DEPLOYED => [
                ['sendDeployCompletedMail'],
            ],
            TokenEvents::CONNECTED => [
                ['sendConnectCompletedMail'],
            ],
        ];
    }

    public function sendDeployCompletedMail(DeployCompletedEvent $event): void
    {
        $user = $event->getToken()->getProfile()->getUser();

        try {
            $this->mailer->sendOwnTokenDeployedMail($event->getToken(), $event->getTokenDeploy());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Couldn't send "
                .TokenEvents::DEPLOYED
                ." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}"
            );
        }

        $notificationType = NotificationTypes::TOKEN_DEPLOYED;
        $strategy = new TokenDeployedNotificationStrategy(
            $this->userNotificationManager,
            $this->mailer,
            $event->getToken(),
            $notificationType
        );
        $notificationContext = new NotificationContext($strategy);
        $followers = $this->userTokenFollowManager->getFollowers($event->getToken());

        foreach ($followers as $follower) {
            $notificationContext->sendNotification($follower);
        }
    }

    public function sendConnectCompletedMail(ConnectCompletedEvent $event): void
    {
        $user = $event->getToken()->getProfile()->getUser();

        try {
            $this->mailer->sendOwnTokenDeployedMail($event->getToken(), $event->getTokenDeploy());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Couldn't send "
                .TokenEvents::DEPLOYED
                ." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}"
            );
        }
    }
}
