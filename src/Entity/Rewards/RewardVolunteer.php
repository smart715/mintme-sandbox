<?php declare(strict_types = 1);

namespace App\Entity\Rewards;

use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\RewardVolunteerRepository")
 * @ORM\Table(name="reward_volunteers",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="reward_volunteer_index",columns={"user_id", "reward_id"})})
 * @ORM\HasLifecycleCallbacks()
 */
class RewardVolunteer implements RewardMemberInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default", "API"})
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @Groups({"Default", "API"})
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rewards\Reward", inversedBy="volunteers")
     * @ORM\JoinColumn(name="reward_id", nullable=false, onDelete="CASCADE")
     */
    private Reward $reward;

    /**
     * @ORM\Column(type="string")
     */
    protected string $price = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"Default", "API"})
     */
    protected ?string $note = null; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    protected \DateTimeImmutable $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
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

    public function isConfirmationRequired(): bool
    {
        return true;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt->getTimestamp();
    }
}
