<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenSignupHistoryRepository")
 * @ORM\Table(name="token_signup_history")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class TokenSignupHistory extends PromotionHistory
{
    public const COMPLETED_STATUS = 'completed';
    public const DELIVERED_STATUS = 'delivered';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="tokenSignupHistory")
     * @ORM\JoinColumn(name="token_id", nullable=false)
     */
    protected Token $token;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Groups({"Default", "API"})
     */
    protected string $status = self::COMPLETED_STATUS; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"Default", "API"})
     */
    protected string $amount;

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(Symbols::TOK));
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getType(): string
    {
        return self::TOKEN_SIGNUP;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }
}
