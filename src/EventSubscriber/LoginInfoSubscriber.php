<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\NewDeviceDetectedEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserLoginInfoManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginInfoSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var UserLoginInfoManagerInterface */
    private $userLoginInfoManager;

    public function __construct(MailerInterface $mailer, UserLoginInfoManagerInterface $userLoginInfoManager)
    {
        $this->mailer = $mailer;
        $this->userLoginInfoManager = $userLoginInfoManager;
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
        $this->userLoginInfoManager->updateUserDeviceLoginInfo($event);
    }

    public function sendNewDeviceDetectedMail(NewDeviceDetectedEvent $event): void
    {
        $this->mailer->sendNewDeviceDetectedMail($event->getUser(), $event->getUserDeviceLoginInfo());
    }
}
