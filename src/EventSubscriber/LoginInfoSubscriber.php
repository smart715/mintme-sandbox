<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Events\NewDeviceDetectedEvent;
use App\Mailer\MailerInterface;
use App\Manager\ProfileManager;
use App\Manager\ProfileManagerInterface;
use App\Manager\UserLoginInfoManagerInterface;
use App\Utils\Facebook\FacebookPixelCommunicator;
use App\Utils\Facebook\FacebookPixelCommunicatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginInfoSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var UserLoginInfoManagerInterface */
    private $userLoginInfoManager;

    /** @var FacebookPixelCommunicatorInterface */
    private $facebookPixelCommunicator;
    
    /** @var ProfileManagerInterface */
    private $profileManager;
    
    public function __construct(
        MailerInterface $mailer,
        UserLoginInfoManagerInterface $userLoginInfoManager,
        FacebookPixelCommunicatorInterface $facebookPixelCommunicator,
        ProfileManagerInterface $profileManager
    ) {
        $this->mailer = $mailer;
        $this->userLoginInfoManager = $userLoginInfoManager;
        $this->facebookPixelCommunicator = $facebookPixelCommunicator;
        $this->profileManager = $profileManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => [['updateLoginDeviceInfo', 0], ['sendFacebookPixelEvent', 1]],
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
    
    public function sendFacebookPixelEvent(InteractiveLoginEvent $event): void
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $this->facebookPixelCommunicator->sendUserEvent(
            'Login',
            $user->getEmail(),
            $event->getRequest()->getClientIp(),
            $event->getRequest()->headers->get('User-Agent'),
            [],
            $this->profileManager->getProfile($user)
        );
    }
}
