<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DonationRepository")
 * @ORM\Table(
 *     name="donation",
 *     indexes={
 *          @ORM\Index(name="FK_31E581A041DEE7B9", columns={"token_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Donation
{
    public const TYPE_FULL_DONATION = 'donation';
    public const TYPE_PARTIAL = 'partial-donation';
    public const TYPE_FULL_BUY = 'direct-buy';

    public const TYPES = [
        self::TYPE_FULL_DONATION,
        self::TYPE_PARTIAL,
        self::TYPE_FULL_BUY,
    ];

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="donor_id", referencedColumnName="id", nullable=false)
     * @var User
     */
    private $donor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="token_creator_id", referencedColumnName="id", nullable=false)
     * @var User
     */
    private $tokenCreator;

    /**
     * @ORM\Column(type="string", length=6)
     * @var string
     */
    private $currency;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $amount;

    /**
     * @ORM\Column(name="fee_amount", type="string")
     * @var string
     */
    private $feeAmount;

    /**
     * @ORM\Column(name="token_amount", type="string", nullable=true, options={"default": 0})
     * @var string
     */
    private $tokenAmount = '0';

    /**
     * @ORM\Column(name="created_at", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private ?Token $token;

    /**
     * @ORM\Column(type="string", nullable=true, name="receiver_amount")
     */
    protected ?string $receiverAmount;

    /**
     * @ORM\Column(type="string", nullable=true, name="receiver_fee_amount")
     */
    protected ?string $receiverFeeAmount;

    /**
     * @ORM\Column(type="string", options={"default": Symbols::WEB})
     */
    protected string $receiverCurrency = Symbols::WEB; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private string $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="referencer_id", referencedColumnName="id", nullable=true)
     */
    private ?User $referencer;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $referencerAmount;


    public function getId(): int
    {
        return $this->id;
    }

    public function getDonor(): User
    {
        return $this->donor;
    }

    public function setDonor(User $donor): self
    {
        $this->donor = $donor;

        return $this;
    }

    public function getTokenCreator(): User
    {
        return $this->tokenCreator;
    }

    public function setTokenCreator(User $tokenCreator): self
    {
        $this->tokenCreator = $tokenCreator;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getFeeAmount(): Money
    {
        return new Money($this->feeAmount, new Currency($this->currency));
    }

    public function setFeeAmount(Money $feeAmount): self
    {
        $this->feeAmount = $feeAmount->getAmount();

        return $this;
    }

    public function getTokenAmount(): Money
    {
        return new Money($this->tokenAmount, new Currency(Symbols::TOK));
    }

    public function setTokenAmount(Money $tokenAmount): self
    {
        $this->tokenAmount = $tokenAmount->getAmount();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function getReceiverAmount(): ?Money
    {
        return $this->receiverAmount
            ? new Money($this->receiverAmount, new Currency($this->receiverCurrency))
            : null;
    }

    public function setReceiverAmount(Money $receiverAmount): self
    {
        $this->receiverAmount = $receiverAmount->getAmount();

        return $this;
    }

    public function getReceiverFeeAmount(): ?Money
    {
        return $this->receiverFeeAmount
            ? new Money($this->receiverFeeAmount, new Currency($this->receiverCurrency))
            : null;
    }

    public function setReceiverFeeAmount(Money $receiverFeeAmount): self
    {
        $this->receiverFeeAmount = $receiverFeeAmount->getAmount();

        return $this;
    }

    public function getReceiverCurrency(): string
    {
        return $this->receiverCurrency;
    }

    public function setReceiverCurrency(string $receiverCurrency): self
    {
        $this->receiverCurrency = $receiverCurrency;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException('Invalid donation type');
        }

        $this->type = $type;

        return $this;
    }

    public function getReferencer(): ?User
    {
        return $this->referencer;
    }

    public function setReferencer(User $referencer): self
    {
        $this->referencer = $referencer;

        return $this;
    }

    public function getReferencerAmount(): ?Money
    {
        return $this->referencerAmount
            ? new Money($this->referencerAmount, new Currency(Symbols::WEB))
            : null;
    }

    public function setReferencerAmount(Money $referencerAmount): self
    {
        $this->referencerAmount = $referencerAmount->getAmount();

        return $this;
    }
}
