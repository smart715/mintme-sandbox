<?php

namespace App\EventListener;

use App\Manager\ProfileManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;

/**
 * Listener of the event "visitor submitted a valid registration form and his User is
 * saved". The purpose is to create supplementary Profile entity which will hold our
 * application-specific data, without data needed for authentication which is held by
 * User entity.
 */
class RegistrationCompletedListener
{
    /** @var ProfileManagerInterface */
    private $profileManager;

    /** @var FilterUserResponseEvent $event */
    private $event;

    public function __construct(ProfileManagerInterface $profileManager)
    {
        $this->profileManager = $profileManager;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        $this->event = $event;
        $this->createProfile();
    }

    private function createProfile(): void
    {
        $user = $this->event->getUser();

        if (!is_null($this->profileManager->getProfile($user)))
            return;
        elseif (!is_null($this->extractReferralCode()))
            $this->profileManager->createProfileReferral(
                $user,
                $this->extractReferralCode()
            );
        else $this->profileManager->createProfile($user);
    }

    private function extractReferralCode(): ?string
    {
        return $this->event->getRequest()->cookies->get('referral')
            ?? $this->event->getRequest()->getSession()->get('referral')
            ?? null;
    }
}
