<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\NewDeviceDetectedEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserLoginInfoInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginInfoSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var UserLoginInfoInterface */
    private $userLoginInfo;

    public function __construct(MailerInterface $mailer, UserLoginInfoInterface $userLoginInfo)
    {
        $this->mailer = $mailer;
        $this->userLoginInfo = $userLoginInfo;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'updateLoginDeviceInfo',
            NewDeviceDetectedEvent::NAME => 'sendNewDeviceDetectedMail',

        ];
    }

    public function updateLoginDeviceInfo(InteractiveLoginEvent $event): void
    {
        $this->userLoginInfo->updateUserDeviceLoginInfo($event);
    }

    public function sendNewDeviceDetectedMail(NewDeviceDetectedEvent $event): void
    {
        $this->mailer->sendNewDeviceDetectedMail($event->getUser(), $event->getUserDeviceLoginInfo());
    }
}
