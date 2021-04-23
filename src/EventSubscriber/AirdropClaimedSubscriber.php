<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Events\TokenEvents;
use App\Events\UserAirdropEvent;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AirdropClaimedSubscriber implements EventSubscriberInterface
{

    private MailerInterface $mailer;
    private AirdropReferralCodeManager $arcManager;
    private SessionInterface $session;
    private AirdropCampaignManagerInterface $airdropCampaignManager;

    public function __construct(
        MailerInterface $mailer,
        AirdropReferralCodeManager $arcManager,
        SessionInterface $session,
        AirdropCampaignManagerInterface $airdropCampaignManager
    ) {
        $this->mailer = $mailer;
        $this->arcManager = $arcManager;
        $this->session = $session;
        $this->airdropCampaignManager = $airdropCampaignManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CLAIMED => 'sendMailForAirdropClaimed',
            SecurityEvents::INTERACTIVE_LOGIN => 'claimedAirdropFromSessionData',
        ];
    }

    public function sendMailForAirdropClaimed(UserAirdropEvent $event): void
    {
        $token = $event->getToken();
        $user = $event->getUser();
        $airdrop = $event->getAirdrop();
        $airdropReward = $airdrop->getReward();
        $airdropReferralHash = $this->arcManager->getByAirdropAndUser($airdrop, $user);
        $airdropReferralCode = $this->arcManager->encode($airdropReferralHash);

        $this->mailer->sendAirdropClaimedMail($user, $token, $airdropReward, $airdropReferralCode);
    }

    public function claimedAirdropFromSessionData(InteractiveLoginEvent $event): void
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $this->airdropCampaignManager->claimAirdropsActionsFromSessionData($user);
    }
}
