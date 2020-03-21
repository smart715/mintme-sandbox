<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\AirdropCampaign\AirdropRepository;
use Doctrine\ORM\EntityManagerInterface;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createAirdrop(
        Token $token,
        string $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop {
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

        return $airdrop;
    }

    public function deleteAirdrop(Airdrop $airdrop): void
    {
        $airdrop->setStatus(Airdrop::STATUS_REMOVED);

        $this->em->persist($airdrop);
        $this->em->flush();
    }

    public function deleteActiveAirdrop(Token $token): void
    {
        $existingAirdrop = $token->getActiveAirdrop();

        if ($existingAirdrop && Airdrop::STATUS_ACTIVE === $existingAirdrop->getStatus()) {
            $this->deleteAirdrop($existingAirdrop);
        }
    }

    public function showAirdropCampaign(User $user, Token $token): bool
    {
        if (!$token->getActiveAirdrop()) {
            return false;
        }

        $participant = $this
            ->getParticipantRepository()
            ->getParticipantByUserAndToken($user, $token->getActiveAirdrop());

        return null === $participant;
    }

    public function getAirdropRepository(): AirdropRepository
    {
        return $this->em->getRepository(Airdrop::class);
    }

    public function getParticipantRepository(): AirdropParticipantRepository
    {
        return $this->em->getRepository(AirdropParticipant::class);
    }
}
