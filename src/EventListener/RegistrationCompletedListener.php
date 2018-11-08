<?php

namespace App\EventListener;

use App\Manager\UserReferralManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;

/**
 * Listener of the event "visitor submitted a valid registration form and his User is
 * saved". The purpose is to create supplementary Profile entity which will hold our
 * application-specific data, without data needed for authentication which is held by
 * User entity.
 */
class RegistrationCompletedListener
{
    /** @var UserReferralManagerInterface */
    private $userReferralManager;

    /** @var FilterUserResponseEvent $event */
    private $event;

    public function __construct(UserReferralManagerInterface $userReferralManager)
    {
        $this->userReferralManager = $userReferralManager;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        $this->event = $event;
        $this->addReferralcode();
    }

    private function addReferralcode(): void
    {
        $userId = $userId = $this->event->getUser()->getId();

        if (!is_null($this->extractReferralCode()))
            $this->userReferralManager->createUserReferral(
                $userId,
                $this->extractReferralCode()
            );
        else $this->userReferralManager->createUserReferral($userId, null);
    }

    private function extractReferralCode(): ?string
    {
        return $this->event->getRequest()->cookies->get('referral')
            ?? $this->event->getRequest()->getSession()->get('referral')
            ?? null;
    }
}
