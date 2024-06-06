<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Wallet\Model\Status;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenCryptoRepository")
 * @ORM\Table(
 *     name="token_crypto",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="token_crypto_index", columns={"crypto_id", "token_id"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class TokenCrypto implements PromotionHistoryInterface
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="crypto_id", nullable=false, onDelete="CASCADE")
     */
    private Crypto $crypto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", cascade={"persist"}, inversedBy="exchangeCryptos")
     * @ORM\JoinColumn(name="token_id", nullable=false)
     */
    private Token $token;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $cost = null; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Crypto $cryptoCost = null; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    protected $created;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function setCost(Money $cost): self
    {
        $this->cost = $cost->getAmount();

        return $this;
    }

    public function setCryptoCost(?Crypto $cryptoCost): self
    {
        $this->cryptoCost = $cryptoCost;

        return $this;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getCryptoCost(): Crypto
    {
        return $this->cryptoCost ?? $this->crypto;
    }

    public function getCost(): Money
    {
        return new Money($this->cost ?? '0', new Currency($this->getCryptoCost()->getMoneySymbol()));
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new DateTimeImmutable();

        return $this;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getType(): string
    {
        return PromotionHistory::TOKEN_NEW_MARKET;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getCreatedAt(): int
    {
        return $this->getCreated()->getTimestamp();
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getAmount(): Money
    {
        return $this->getCost();
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getUser(): User
    {
        return $this->token->getOwner();
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getStatus(): string
    {
        return Status::PAID;
    }
}
