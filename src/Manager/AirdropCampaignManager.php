<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var AirdropParticipantRepository */
    private $participantRepository;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->em = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->participantRepository = $entityManager->getRepository(AirdropParticipant::class);
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

        // TODO: Viabtc - return all tokens that were left if any
    }

    public function deleteActiveAirdrop(Token $token): void
    {
        $existingAirdrop = $token->getActiveAirdrop();

        if ($existingAirdrop && Airdrop::STATUS_ACTIVE === $existingAirdrop->getStatus()) {
            $this->deleteAirdrop($existingAirdrop);
        }
    }

    public function checkIfUserClaimed(?User $user, Token $token): bool
    {
        if ($user instanceof User && $token->getActiveAirdrop() instanceof Airdrop) {
            $participant = $this->participantRepository
                ->getParticipantByUserAndToken($user, $token->getActiveAirdrop());

            return $participant instanceof AirdropParticipant;
        }

        return false;
    }

    public function claimAirdropCampaign(User $user, Token $token): void
    {
        /** @var Airdrop $activeAirdrop */
        $activeAirdrop = $token->getActiveAirdrop();
        $activeAirdrop->incrementActualParticipants();
        $this->calculateActualAmount($activeAirdrop);

        $participant = new AirdropParticipant();
        $participant->setUser($user);
        $participant->setAirdrop($activeAirdrop);

        $this->em->persist($activeAirdrop);
        $this->em->persist($participant);
        $this->em->flush();

        if ($activeAirdrop->getParticipants() === $activeAirdrop->getActualParticipants()) {
            $this->deleteAirdrop($activeAirdrop);
        }

        // TODO: Viabtc - send participant airdrop reward
    }

    public function calculateActualAmount(Airdrop $airdrop): void
    {
        $amount = $this->moneyWrapper->parse(
            $airdrop->getAmount(),
            MoneyWrapper::TOK_SYMBOL
        );
        $participants = $this->moneyWrapper->parse(
            (string)$airdrop->getParticipants(),
            MoneyWrapper::TOK_SYMBOL
        );

        if ($amount->isZero() || !$airdrop->getActualParticipants()) {
            return;
        }

        $airdropReward = (float)$amount->ratioOf($participants);
        $actualAmount = $airdropReward * $airdrop->getActualParticipants();
        $actualAmount = round($actualAmount, 4, PHP_ROUND_HALF_DOWN);

        $airdrop->setActualAmount((string)$actualAmount);
    }
}
