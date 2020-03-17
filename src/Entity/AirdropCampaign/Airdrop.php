<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\Token\Token;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AirdropCampaign\AirdropRepository")
 * @codeCoverageIgnore
 */
class Airdrop
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_REMOVED = 0;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     * @var int
     */
    private $status = self::STATUS_REMOVED;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="airdrop")
     * @ORM\JoinColumn(name="token_id", nullable=false)
     * @var Token
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $amount;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $participants;

    /**
     * @ORM\Column(name="actual_amount", type="string", length=100, nullable=true)
     * @var string|null
     */
    private $actualAmount;

    /**
     * @ORM\Column(name="actual_participants", type="integer", nullable=true)
     * @var int|null
     */
    private $actualParticipants;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AirdropCampaign\Participant", mappedBy="airdrop", orphanRemoval=true)
     * @var ArrayCollection
     */
    private $claimedParticipants;

    public function __construct()
    {
        $this->claimedParticipants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getParticipants(): ?int
    {
        return $this->participants;
    }

    public function setParticipants(int $participants): self
    {
        $this->participants = $participants;

        return $this;
    }

    public function getActualAmount(): ?string
    {
        return $this->actualAmount;
    }

    public function setActualAmount(?string $actualAmount): self
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    public function getActualParticipants(): ?int
    {
        return $this->actualParticipants;
    }

    public function setActualParticipants(?int $actualParticipants): self
    {
        $this->actualParticipants = $actualParticipants;

        return $this;
    }

    /**
     * @return Collection|Participant[]
     */
    public function getClaimedParticipants(): Collection
    {
        return $this->claimedParticipants;
    }

    public function addClaimedParticipant(Participant $claimedParticipant): self
    {
        if (!$this->claimedParticipants->contains($claimedParticipant)) {
            $this->claimedParticipants[] = $claimedParticipant;
            $claimedParticipant->setAirdrop($this);
        }

        return $this;
    }

    public function removeClaimedParticipant(Participant $claimedParticipant): self
    {
        if ($this->claimedParticipants->contains($claimedParticipant)) {
            $this->claimedParticipants->removeElement($claimedParticipant);
        }

        return $this;
    }
}
