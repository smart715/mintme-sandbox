<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\DeployCompletedEvent;
use App\Events\TokenEvent;
use App\Events\TokenEvents;
use App\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeploySubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;

    private LoggerInterface $logger;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::DEPLOYED => [
                ['sendDeployCompletedMail'],
            ],
        ];
    }

    public function sendDeployCompletedMail(DeployCompletedEvent $event): void
    {
        $user = $event->getToken()->getProfile()->getUser();

        try {
            $this->mailer->checkConnection();
            $this->mailer->sendOwnTokenDeployedMail($event->getToken());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Couldn't send "
                .TokenEvents::DEPLOYED
                ." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}"
            );
        }
    }
}
