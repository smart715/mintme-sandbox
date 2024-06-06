<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Events\UserChangeEvents;
use App\Mailer\MailerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
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
            UserChangeEvents::PASSWORD_UPDATED_MSG => 'sendPasswordResetMail',
            FOSUserEvents::RESETTING_RESET_REQUEST => 'resetToken',
        ];
    }

    public function sendPasswordResetMail(FilterUserResponseEvent $event, string $eventName): void
    {
        $resetting = FOSUserEvents::RESETTING_RESET_COMPLETED === $eventName;

        /** @var User */
        $user = $event->getUser();

        $this->mailer->sendPasswordResetMail($user, $resetting);
    }

    public function resetToken(GetResponseUserEvent $event): void
    {
        $event->getUser()->setConfirmationToken(null);
    }
}
