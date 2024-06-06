<?php declare(strict_types = 1);

namespace App\Entity;

use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PendingWithdrawRepository")
 * @ORM\Table(
 *     name="pending_withdraw",
 *     indexes={
 *         @ORM\Index(name="FK_321D93BB37423AA5", columns={"crypto_network_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class PendingWithdraw implements PendingWithdrawInterface
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"API"})
     */
    private DateTimeImmutable $date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @Groups({"API"})
     */
    private Crypto $crypto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @Groups({"API"})
     */
    private Crypto $cryptoNetwork;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     */
    private string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="pendingWithdrawals")
     */
    private User $user;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private string $address;

    /**
     * @ORM\Column(type="string")
     */
    protected string $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, options={"default": "0"})
     */
    private string $fee = '0'; //phpcs:ignore

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $feeCurrency;

    public function __construct(
        User $user,
        Crypto $crypto,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee
    ) {
        $this->user = $user;
        $this->crypto = $crypto;
        $this->cryptoNetwork = $cryptoNetwork;
        $this->amount = $amount->getAmount()->getAmount();
        $this->address = $address->getAddress();
        $this->fee = $fee->getAmount();
        $this->feeCurrency = $fee->getCurrency()->getCode();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function getCryptoNetwork(): Crypto
    {
        return $this->cryptoNetwork;
    }

    public function getAmount(): Amount
    {
        return new Amount(
            new Money(
                $this->amount,
                new Currency($this->crypto->getSymbol())
            )
        );
    }

    public function getAddress(): Address
    {
        return new Address($this->address);
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSymbol(): string
    {
        return $this->getCrypto()->getSymbol();
    }

    /** @ORM\PrePersist() */
    public function init(): self
    {
        $this->hash = hash('sha256', Uuid::uuid4()->toString());
        $this->date = new DateTimeImmutable();

        return $this;
    }

    public function getFee(): Money
    {
        return new Money($this->fee, new Currency($this->feeCurrency));
    }

    public function getFeeCurrency(): ?string
    {
        return $this->feeCurrency;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function setFee(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }
}
