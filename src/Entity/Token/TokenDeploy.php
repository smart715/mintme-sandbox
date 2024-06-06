<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\Crypto;
use App\Entity\PromotionHistory;
use App\Entity\PromotionHistoryInterface;
use App\Entity\User;
use App\Wallet\Model\Status;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenDeployRepository")
 * @ORM\Table(
 *  name="token_deploy",
 *  indexes={
 *     @ORM\Index(name="idx_c13b62d8e9571a63", columns={"crypto_id"}),
 *     @ORM\Index(name="idx_c13b62d841dee7b9", columns={"token_id"})
 *  },
 *  uniqueConstraints={
 *     @ORM\UniqueConstraint(name="IDX_TOKEN_CRYPTO", columns={"token_id", "crypto_id"}),
 *     @ORM\UniqueConstraint(name="IDX_CRYPTO_ADDRESS", columns={"crypto_id", "address"}),
 *     @ORM\UniqueConstraint(name="tx_hash", columns={"tx_hash"})
 *  }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class TokenDeploy implements PromotionHistoryInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="deploys")
     * @ORM\JoinColumn(nullable=false)
     */
    private Token $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $crypto;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $address = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     */
    private ?string $txHash = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $deployCost = null; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $deployDate = null; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    protected DateTimeImmutable $createdAt;

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
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getAddress(): ?string
    {
         return  $this->address;
    }

    public function setTxHash(?string $txHash): self
    {
        $this->txHash = $txHash;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getTxHash(): ?string
    {
         return  $this->txHash;
    }

    public function setDeployCost(?string $cost): self
    {
        $this->deployCost = $cost;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getDeployCost(): ?string
    {
        return $this->deployCost;
    }

    public function setDeployDate(?DateTimeImmutable $date): self
    {
        $this->deployDate = $date;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getDeployDate(): ?DateTimeImmutable
    {
         return  $this->deployDate;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getCreatedAtDate(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTimeImmutable();

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function isPending(): bool
    {
        // If the deploy is in the database with these null values
        // then the deployment is in proccess.
        return null === $this->address
            || null === $this->deployDate;
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getType(): string
    {
        return $this->token->getMainDeploy()->getId() === $this->id
            ? PromotionHistory::TOKEN_DEPLOYMENT
            : PromotionHistory::TOKEN_CONNECTION;
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
    public function getAmount(): Money
    {
        return new Money(
            $this->deployCost ?? '0',
            new Currency($this->crypto->getMoneySymbol())
        );
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt->getTimestamp();
    }

    /**
     * @Groups({"PROMOTION_HISTORY"})
     */
    public function getStatus(): string
    {
        return $this->isPending()
            ? Status::PENDING
            : Status::PAID;
    }
}
