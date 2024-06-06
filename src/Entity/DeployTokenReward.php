<?php declare(strict_types = 1);

namespace App\Entity;

use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeployTokenRewardRepository")
 * @ORM\Table(name="deploy_token_reward")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class DeployTokenReward
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=100, options={"default"="0"})
     */
    private string $reward;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": Symbols::WEB})
     */
    private string $currency = Symbols::WEB; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $created;

    public function __construct(User $user, Money $reward)
    {
        $this->user = $user;
        $this->reward = $reward->getAmount();
        $this->currency = $reward->getCurrency()->getCode();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReward(): Money
    {
        return new Money($this->reward, new Currency($this->currency));
    }

    public function setReward(Money $reward): self
    {
        $this->reward = $reward->getAmount();

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new \DateTimeImmutable();

        return $this;
    }
}
