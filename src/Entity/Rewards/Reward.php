<?php declare(strict_types = 1);

namespace App\Entity\Rewards;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RewardRepository")
 * @ORM\Table(name="reward")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Reward
{
    public const TYPE_REWARD = 'reward';
    public const TYPE_BOUNTY = 'bounty';
    public const STATUS_ACTIVE = 1;
    public const STATUS_DELETED = 0;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected string $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Groups({"Default", "API"})
     */
    protected Token $token;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(
     *    min = 3,
     *    max = 100,
     * )
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected string $title = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected string $slug = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected ?string $description = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected string $price = '0'; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Rewards\RewardParticipant",
     *     mappedBy="reward",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY")
     * @Groups({"Default", "API"})
     */
    protected Collection $participants;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Rewards\RewardVolunteer",
     *     mappedBy="reward",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY")
     * @Groups({"Default", "API"})
     */
    protected Collection $volunteers;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"API", "API_BASIC"})
     */
    protected int $quantity = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     */
    protected string $frozenAmount = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 1}))
     */
    protected int $status = self::STATUS_ACTIVE; // phpcs:ignore

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->volunteers = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title = null): self  // phpcs:ignore
    {
        if (empty($title)) {
            $title= '';
        }

        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): Money
    {
        return new Money($this->price, new Currency(Symbols::TOK));
    }

    public function setPrice(Money $price): self
    {
        $this->price = $price->getAmount();

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * @Groups({"Default", "API"})
     * @SerializedName("participants")
    */
    public function getParticipantsArray(): array
    {
        return $this->getParticipants()->getValues();
    }

    public function addParticipant(RewardParticipant $rewardParticipant): self
    {
        if (!$this->participants->contains($rewardParticipant)) {
            $this->participants->add($rewardParticipant);
        }

        return $this;
    }

    public function getVolunteers(): Collection
    {
        return $this->volunteers;
    }

    /**
     * @Groups({"Default", "API"})
     * @SerializedName("volunteers")
    */
    public function getVolunteersArray(): array
    {
        return $this->getVolunteers()->getValues();
    }

    public function addVolunteer(RewardVolunteer $rewardVolunteer): self
    {
        if (!$this->volunteers->contains($rewardVolunteer)) {
            $this->volunteers->add($rewardVolunteer);
        }

        return $this;
    }

    public function removeVolunteer(RewardVolunteer $volunteer): self
    {
        if ($this->volunteers->contains($volunteer)) {
            $this->volunteers->removeElement($volunteer);
        }

        return $this;
    }

    public function addMember(RewardMemberInterface $member): self
    {
        if ($member instanceof RewardParticipant) {
            $this->addParticipant($member);
        }

        if ($member instanceof RewardVolunteer) {
            $this->addVolunteer($member);
        }

        return $this;
    }

    public function removeParticipant(RewardParticipant $participant): self
    {
        if ($this->participants->contains($participant)) {
            $this->participants->removeElement($participant);
        }

        return $this;
    }

    public function isRewardContainVolunteer(RewardVolunteer $rewardVolunteer): bool
    {
        return $this->volunteers->contains($rewardVolunteer);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt->getTimestamp();
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getFrozenAmount(): Money
    {
        return new Money($this->frozenAmount, new Currency(Symbols::TOK));
    }

    public function setFrozenAmount(Money $frozenAmount): self
    {
        $this->frozenAmount = $frozenAmount->getAmount();

        return $this;
    }

    // If Reward has bounty type, then token owner should pay to user. If reward type, then user pay to token owner.
    public function isBountyType(): bool
    {
        return self::TYPE_BOUNTY === $this->type;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getActiveParticipantsAmount(): int
    {
        return $this->participants->filter(fn($participant) => !$participant->isCancelled())->count();
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function isQuantityReached(): bool
    {
        if (0 === $this->quantity) {
            return false;
        }

        return $this->getActiveParticipantsAmount() >= $this->quantity;
    }

    public function hasPendingParticipants(): bool
    {
        return count(array_filter(
            $this->participants->toArray(),
            fn(RewardParticipant $participant) => $participant->isPending()
        )) > 0;
    }

    public function isFinishedReward(): bool
    {
        return $this->isQuantityReached() && !$this->hasPendingParticipants();
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
