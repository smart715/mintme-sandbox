<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\PromotionHistory;
use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class TokenPromotion extends PromotionHistory
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tokenPromotions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": Symbols::WEB})
     */
    private string $currency = Symbols::WEB; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity="Token", inversedBy="promotions")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    protected \DateTimeImmutable $endDate;

    public function __construct(
        User $user,
        Token $token,
        \DateTimeImmutable $endDate,
        Money $amount,
        string $currency
    ) {
        $this->token = $token;
        $this->endDate = $endDate;
        $this->user = $user;
        $this->currency = $currency;
        $this->setAmount($amount);
    }

    public function getId(): int
    {
        return $this->id;
    }
    

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getType(): string
    {
        return self::TOKEN_PROMOTION;
    }
}
