<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

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
     * @Groups({"API"})
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     * @Groups({"API"})
     * @var int
     */
    private $status = self::STATUS_REMOVED;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="airdrops")
     * @ORM\JoinColumn(name="token_id", nullable=false)
     * @var Token
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"API"})
     * @var string
     */
    private $amount = '0';

    /**
     * @ORM\Column(name="locked_amount", type="string", length=100)
     * @Groups({"API"})
     * @var string
     */
    private $lockedAmount = '0';

    /**
     * @ORM\Column(type="integer")
     * @Groups({"API"})
     * @var int
     */
    private $participants;

    /**
     * @ORM\Column(name="end_date", type="datetime_immutable", nullable=true)
     * @Groups({"API"})
     * @var \DateTimeImmutable|null
     */
    private $endDate;

    /**
     * @ORM\Column(name="actual_amount", type="string", length=100, nullable=true)
     * @var string
     */
    private $actualAmount = '0';

    /**
     * @ORM\Column(name="actual_participants", type="integer", nullable=true)
     * @Groups({"API"})
     * @var int
     */
    private $actualParticipants = 0;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\AirdropCampaign\AirdropParticipant",
     *     mappedBy="airdrop",
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @var ArrayCollection
     */
    private $claimedParticipants;

    public function __construct()
    {
        $this->claimedParticipants = new ArrayCollection();
    }

    public function getId(): int
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

    public function isActive(): ?bool
    {
        return self::STATUS_ACTIVE === $this->getStatus();
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

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getLockedAmount(): Money
    {
        return new Money($this->lockedAmount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    public function setLockedAmount(Money $lockedAmount): self
    {
        $this->lockedAmount = $lockedAmount->getAmount();

        return $this;
    }

    public function getParticipants(): int
    {
        return $this->participants;
    }

    public function setParticipants(int $participants): self
    {
        $this->participants = $participants;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getActualAmount(): Money
    {
        return new Money($this->actualAmount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    public function setActualAmount(Money $actualAmount): self
    {
        $this->actualAmount = $actualAmount->getAmount();

        return $this;
    }

    public function getActualParticipants(): int
    {
        return $this->actualParticipants;
    }

    public function setActualParticipants(int $actualParticipants): self
    {
        $this->actualParticipants = $actualParticipants;

        return $this;
    }

    public function incrementActualParticipants(): int
    {
        return $this->actualParticipants += 1;
    }

    public function getClaimedParticipants(): Collection
    {
        return $this->claimedParticipants;
    }

    public function addClaimedParticipant(AirdropParticipant $claimedParticipant): self
    {
        if (!$this->claimedParticipants->contains($claimedParticipant)) {
            $this->claimedParticipants->add($claimedParticipant);
            $claimedParticipant->setAirdrop($this);
        }

        return $this;
    }

    public function removeClaimedParticipant(AirdropParticipant $claimedParticipant): self
    {
        if ($this->claimedParticipants->contains($claimedParticipant)) {
            $this->claimedParticipants->removeElement($claimedParticipant);
        }

        return $this;
    }
}