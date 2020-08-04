<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\UserManagerInterface;
use App\Utils\Facebook\FacebookPixelCommunicator;
use App\Utils\Facebook\FacebookPixelCommunicatorInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class RegistrationCompletedListener
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var FilterUserResponseEvent|null */
    private $event;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var FacebookPixelCommunicatorInterface */
    private $facebookPixelCommunicator;
    
    public function __construct(
        UserManagerInterface $userManager,
        UserActionLogger $userActionLogger,
        FacebookPixelCommunicatorInterface $facebookPixelCommunicator
    ) {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
        $this->facebookPixelCommunicator = $facebookPixelCommunicator;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        $this->event = $event;
        $this->updateReferral();
        $this->event = null;
        $this->sendFacebookPixelEvent($event->getUser()->getEmail());
        
        $this->userActionLogger->info('Register ' . $event->getUser()->getEmail());
    }

    private function updateReferral(): void
    {
        /** @var User $user */
        $user = $this->event->getUser();
        $referrencer = $this->userManager->findByReferralCode($this->extractReferralCode());

        if (!$referrencer) {
            return;
        }

        $user->setReferrencer($referrencer);
        $this->userManager->updateUser($user);
    }

    private function extractReferralCode(): string
    {
        return $this->event->getRequest()->cookies->get('referral-code') ?? '';
    }
    
    private function sendFacebookPixelEvent(string $userEmail): void
    {
        $request = Request::createFromGlobals();
        
        $this->facebookPixelCommunicator->sendUserEvent(
            'Registration',
            $userEmail,
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
            [],
            null
        );
    }
}
