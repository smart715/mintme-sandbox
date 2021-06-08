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
 * @ORM\Table(name="pending_withdraw")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class PendingWithdraw implements PendingWithdrawInterface
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected int $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"API"})
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @Groups({"API"})
     * @var Crypto
     */
    private Crypto $crypto;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     * @var string
     */
    private $amount = '0';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="pendingWithdrawals")
     * @var User
     */
    private User $user;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected string $hash;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $fee;


    public function __construct(User $user, Crypto $crypto, Amount $amount, Address $address, Money $fee)
    {
        $this->user = $user;
        $this->crypto = $crypto;
        $this->amount = $amount->getAmount()->getAmount();
        $this->address = $address->getAddress();
        $this->fee = $fee->getAmount();
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
        return new Money(
            $this->fee,
            new Currency($this->crypto->getSymbol())
        );
    }
}
