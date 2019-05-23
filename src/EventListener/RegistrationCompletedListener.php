<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;

class RegistrationCompletedListener
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var FilterUserResponseEvent|null */
    private $event;

    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(UserManagerInterface $userManager, UserActionLogger $userActionLogger)
    {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        $this->event = $event;
        $this->updateReferral();
        $this->event = null;

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
}
