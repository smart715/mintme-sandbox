<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var AirdropParticipantRepository */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->repository = $entityManager->getRepository(Airdrop::class);
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

    public function getRepository(): AirdropParticipantRepository
    {
        return $this->repository;
    }
}
