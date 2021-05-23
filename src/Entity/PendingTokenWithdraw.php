<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
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
 * @ORM\Entity(repositoryClass="App\Repository\PendingTokenWithdrawRepository")
 * @ORM\Table(name="pending_token_withdraw")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class PendingTokenWithdraw implements PendingWithdrawInterface
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @Groups({"API"})
     * @var Token
     */
    private Token $token;

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

    public function __construct(User $user, Token $token, Amount $amount, Address $address)
    {
        $this->user = $user;
        $this->token = $token;
        $this->amount = $amount->getAmount()->getAmount();
        $this->address = $address->getAddress();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getAmount(): Amount
    {
        return new Amount(
            new Money(
                $this->amount,
                new Currency(Symbols::TOK)
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
        return $this->getToken()->getSymbol();
    }

    /** @ORM\PrePersist() */
    public function init(): self
    {
        $this->hash = hash('sha256', Uuid::uuid4()->toString());
        $this->date = new DateTimeImmutable();

        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setFee(string $fee): self
    {
        $this->fee = $fee;

        return $this;
    }
}
