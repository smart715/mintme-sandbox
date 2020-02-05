<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use FOS\UserBundle\FOSUserEvents;

class ResettingSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::RESETTING_RESET_COMPLETED => 'sendPasswordResetMail',
        ];
    }

    public function sendPasswordResetMail(FilterUserResponseEven $event): void
    {
        $this->mailer->sendPasswordResetMail($event->getUser());
    }
}
