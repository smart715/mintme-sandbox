<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Mailer\MailerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'sendPasswordResetMail'
        ];
    }

    public function sendPasswordResetMail(FilterUserResponseEvent $event): void
    {
        /** @var User */
        $user = $event->getUser();

        $this->mailer->sendPasswordResetMail($user);
    }
}
