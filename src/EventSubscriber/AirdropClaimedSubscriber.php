<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\TokenEvents;
use App\Events\UserAirdropEvent;
use App\Mailer\MailerInterface;
use App\Manager\AirdropReferralCodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AirdropClaimedSubscriber implements EventSubscriberInterface
{

    private MailerInterface $mailer;
    private AirdropReferralCodeManager $arcManager;

    public function __construct(MailerInterface $mailer, AirdropReferralCodeManager $arcManager)
    {
        $this->mailer = $mailer;
        $this->arcManager = $arcManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CLAIMED => 'sendMailForAirdropClaimed',
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
}
