<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\DeployCompletedEvent;
use App\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeploySubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var LoggerInterface */
    private $logger;

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
            DeployCompletedEvent::NAME => [
                ['sendDeployCompletedMail'],
            ],
        ];
    }

    public function sendDeployCompletedMail(DeployCompletedEvent $event): void
    {
        $user = $event->getUser();

        try {
            $this->mailer->checkConnection();
            $this->mailer->sendOwnTokenDeployedMail($user, $event->getTokenName(), $event->getTxHash());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Couldn't send "
                .$event::TYPE
                ." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}"
            );
        }
    }
}
