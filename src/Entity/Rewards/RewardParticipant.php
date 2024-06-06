<?php declare(strict_types = 1);

namespace App\Entity\Rewards;

use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RewardParticipantRepository")
 * @ORM\Table(name="reward_participants")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class RewardParticipant extends PromotionHistory implements RewardMemberInterface
{
    public const PENDING_STATUS = 'pending';
    public const NOT_COMPLETED_STATUS = 'not_completed';
    public const COMPLETED_STATUS = 'completed';
    public const REFUNDED_STATUS = 'refunded';
    public const DELIVERED_STATUS = 'delivered';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default", "API"})
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rewards\Reward", inversedBy="participants")
     * @ORM\JoinColumn(name="reward_id", nullable=false, onDelete="CASCADE")
     */
    private Reward $reward;

    /**
     * @ORM\Column(type="string")
     */
    protected string $price = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $bonusPrice = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Groups({"Default", "API"})
     */
    protected string $status = self::PENDING_STATUS; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"Default", "API"})
     */
    protected ?string $note = null; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"Default", "API"})
     */
    protected \DateTimeImmutable $createdAt;


    public function getId(): int
    {
        return $this->id;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function setReward(Reward $reward): self
    {
        $this->reward = $reward;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

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

    public function getBonusPrice(): Money
    {
        return new Money($this->bonusPrice ?? '0', new Currency(Symbols::TOK));
    }

    public function setBonusPrice(Money $bonusPrice): self
    {
        $this->bonusPrice = $bonusPrice->getAmount();

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     * @SerializedName("price")
    */
    public function getFullPrice(): Money
    {
        return $this->getPrice()->add($this->getBonusPrice());
    }

    public function isConfirmationRequired(): bool
    {
        return false;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function isCompleted(): bool
    {
        return self::TOKEN_SHOP !== $this->getType()
            ? in_array($this->getStatus(), [self::COMPLETED_STATUS, self::REFUNDED_STATUS, self::DELIVERED_STATUS])
            : in_array($this->getStatus(), [self::REFUNDED_STATUS, self::DELIVERED_STATUS]);
    }

    /**
     * @Groups({"Default", "API"})
     * @SerializedName("isCancelled")
    */
    public function isCancelled(): bool
    {
        return self::REFUNDED_STATUS === $this->getStatus();
    }

    /**
     * @Groups({"Default", "API"})
     * @SerializedName("isPending")
    */
    public function isPending(): bool
    {
        return !$this->isCompleted() && !$this->isCancelled();
    }

    public function getToken(): Token
    {
        return $this->getReward()->getToken();
    }

    public function getAmount(): Money
    {
        return $this->getFullPrice();
    }

    public function getType(): string
    {
        return Reward::TYPE_REWARD === $this->getReward()->getType()
            ? self::TOKEN_SHOP
            : self::BOUNTY;
    }
}
