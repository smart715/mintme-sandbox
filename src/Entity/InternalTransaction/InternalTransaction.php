<?php declare(strict_types = 1);

namespace App\Entity\InternalTransaction;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InternalTransactionRepository")
 * @ORM\Table(name="internal_transaction")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "internalTransaction"="InternalTransaction",
 *     "tokenInternalTransaction"="TokenInternalTransaction",
 *     "cryptoInternalTransaction"="CryptoInternalTransaction"
 * })
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
abstract class InternalTransaction
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
    private Crypto $cryptoNetwork;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     */
    protected string $amount = '0'; // phpcs:ignore

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
     * @ORM\Column(type="string", length=255)
     */
    private string $fee;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $feeCurrency;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $type;

    public function __construct(
        User $user,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee,
        string $type
    ) {
        $this->user = $user;
        $this->cryptoNetwork = $cryptoNetwork;
        $this->amount = $amount->getAmount()->getAmount();
        $this->address = $address->getAddress();
        $this->fee = $fee->getAmount();
        $this->feeCurrency = $fee->getCurrency()->getCode();
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCryptoNetwork(): Crypto
    {
        return $this->cryptoNetwork;
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

    public function getFee(): Money
    {
        return new Money($this->fee, new Currency($this->feeCurrency));
    }

    public function setFee(Money $fee): self
    {
        $this->fee = $fee->getAmount();

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** @ORM\PrePersist() */
    public function setDate(): self
    {
        $this->date = new DateTimeImmutable();

        return $this;
    }

    abstract public function getAmount(): Amount;

    abstract public function getSymbol(): string;

    abstract public function getTradable(): TradableInterface;
}
