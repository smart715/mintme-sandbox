<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;

class RegistrationCompletedListener
{
    private UserManagerInterface $userManager;
    private UserActionLogger $userActionLogger;
    private AirdropReferralCodeManagerInterface $arcManager;

    public function __construct(
        UserManagerInterface $userManager,
        UserActionLogger $userActionLogger,
        AirdropReferralCodeManagerInterface $arcManager
    ) {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
        $this->arcManager = $arcManager;
    }

    public function onFosuserRegistrationCompleted(FilterUserResponseEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $request = $event->getRequest();

        $this->userActionLogger->info('Register ' . $event->getUser()->getEmail());
        $user->addRole(User::ROLE_SEMI_AUTHENTICATED);
        $this->userManager->updateUser($user);

        $referralType = $request->cookies->get('referral-type');

        if ($referralType) {
            $referralCode = $request->cookies->get('referral-code', '');
            $this->handleReferral($user, $referralCode, $referralType);
        }
    }

    private function handleReferral(User $user, string $referralCode, string $referralType): void
    {
        switch ($referralType) {
            case 'invite':
                $this->updateReferral($user, $referralCode);

                break;
            case 'airdrop':
                $this->updateAirdropReferral($user, $referralCode);

                break;
        }
    }

    private function updateReferral(User $user, string $referralCode): void
    {
        $referencer = $this->userManager->findByReferralCode($referralCode);

        if (!$referencer) {
            return;
        }

        $user->setReferencer($referencer);

        $this->userManager->updateUser($user);
    }

    private function updateAirdropReferral(User $user, string $referralCode): void
    {
        $arc = $this->arcManager->decode($referralCode);

        if (!$arc) {
            return;
        }

        $user->setAirdropReferrer($arc->getAirdrop())->setAirdropReferrerUser($arc->getUser());

        $this->userManager->updateUser($user);
    }
}
