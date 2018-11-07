<?php

namespace App\EventListener;

use App\Manager\UserManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;

/**
 * Listener of the event "visitor submitted a valid registration form and his User is
 * saved". The purpose is to create supplementary Profile entity which will hold our
 * application-specific data, without data needed for authentication which is held by
 * User entity.
 */
class RegistrationCompletedListener
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var FilterUserResponseEvent $event */
    private $event;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        $this->event = $event;
        $this->addReferralcode();
    }

    private function addReferralcode(): void
    {
        $user = $this->event->getUser();

        if (!is_null($this->extractReferralCode()))
            $this->userManager->createUserReferral(
                $user,
                $this->extractReferralCode()
            );
        else $this->userManager->createUserReferral($user, null);
    }

    private function extractReferralCode(): ?string
    {
        return $this->event->getRequest()->cookies->get('referral')
            ?? $this->event->getRequest()->getSession()->get('referral')
            ?? null;
    }
}
