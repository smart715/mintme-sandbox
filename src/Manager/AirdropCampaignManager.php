<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var AirdropParticipantRepository */
    private $participantRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->participantRepository = $entityManager->getRepository(AirdropParticipant::class);
    }

    public function createAirdrop(
        Token $token,
        string $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): void {
        $this->deleteActiveAirdrop($token);

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $airdrop->setToken($token);
        $airdrop->setAmount($amount);
        $airdrop->setParticipants($participants);

        if ($endDate instanceof \DateTimeImmutable && $endDate->getTimestamp() > time()) {
            $airdrop->setEndDate($endDate);
        }

        $this->em->persist($airdrop);
        $this->em->flush();
    }

    public function deleteAirdrop(Airdrop $airdrop): void
    {
        $airdrop->setStatus(Airdrop::STATUS_REMOVED);

        $this->em->persist($airdrop);
        $this->em->flush();

        // TODO: Viabtc - return all tokens that were left if any
    }

    public function deleteActiveAirdrop(Token $token): void
    {
        $existingAirdrop = $token->getActiveAirdrop();

        if ($existingAirdrop && Airdrop::STATUS_ACTIVE === $existingAirdrop->getStatus()) {
            $this->deleteAirdrop($existingAirdrop);
        }
    }

    public function showAirdropCampaign(?User $user, Token $token): bool
    {
        if ($user instanceof User && $token->getActiveAirdrop() instanceof Airdrop) {
            $participant = $this->participantRepository
                ->getParticipantByUserAndToken($user, $token->getActiveAirdrop());

            return null === $participant;
        }

        return false;
    }

    public function claimAirdropCampaign(User $user, Token $token): void
    {
        /** @var Airdrop $activeAirdrop */
        $activeAirdrop = $token->getActiveAirdrop();
        $activeAirdrop->incrementActualParticipants();

        $participant = new AirdropParticipant();
        $participant->setUser($user);
        $participant->setAirdrop($activeAirdrop);

        $this->em->persist($activeAirdrop);
        $this->em->persist($participant);
        $this->em->flush();

        // TODO: Viabtc - send participant airdrop reward
    }
}
