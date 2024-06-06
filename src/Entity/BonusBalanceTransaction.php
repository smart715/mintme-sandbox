<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\BonusBalanceTransactionRepository")
 * @ORM\Table(
 *     name="bonus_balance_transactions",
 *     indexes={
 *         @ORM\Index(name="fk_bonusbalancetransactions_users", columns={"user_id"}),
 *         @ORM\Index(name="fk_bonusbalancetransactions_tokens", columns={"token_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class BonusBalanceTransaction
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Token $token;

     /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="crypto_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Crypto $crypto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $amount;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $bonusType;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private DateTimeImmutable $createdAt;

    public function __construct(TradableInterface $tradable)
    {
        $this->setTradable($tradable);
    }

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

    /** @psalm-suppress all */
    public function getTradable(): TradableInterface
    {
        return $this->token ?? $this->crypto;
    }

    public function setTradable(TradableInterface $tradable): self
    {
        if ($tradable instanceof Token) {
            return $this->setToken($tradable);
        }

        if ($tradable instanceof Crypto) {
            return $this->setCrypto($tradable);
        }

        throw new \InvalidArgumentException('Invalid tradable type');
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getAmount(): Money
    {
        return new Money(
            $this->amount,
            new Currency($this->getTradable()->getMoneySymbol())
        );
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
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

    public function getBonusType(): string
    {
        return $this->bonusType;
    }

    public function setBonusType(string $bonusType): self
    {
        $this->bonusType = $bonusType;

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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
