<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AirdropCampaign\AirdropParticipantRepository")
 * @ORM\Table(
 *     name="airdrop_participants",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="airdrop_participant_index", columns={"user_id", "airdrop_id", "referral_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class AirdropParticipant extends PromotionHistory
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop", inversedBy="claimedParticipants")
     * @ORM\JoinColumn(name="airdrop_id", nullable=false)
     * @var Airdrop
     */
    protected Airdrop $airdrop;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="referral_id", nullable=true)
     */
    protected ?User $referral;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAirdrop(): ?Airdrop
    {
        return $this->airdrop;
    }

    public function setAirdrop(Airdrop $airdrop): self
    {
        $this->airdrop = $airdrop;

        return $this;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getToken(): Token
    {
        return $this->getAirdrop()->getToken();
    }

    public function getAmount(): Money
    {
        return $this->referral
            ? $this->getAirdrop()->getReward()->divide(2)
            : $this->getAirdrop()->getReward();
    }

    public function getType(): string
    {
        return self::AIRDROP;
    }

    public function setReferral(?User $user): self
    {
        $this->referral = $user;

        return $this;
    }
}
