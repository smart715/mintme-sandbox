<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Money\MoneyWrapper;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PendingWithdrawRepository")
 * @ORM\Table(name="pending_token_withdraw")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class PendingTokenWithdraw implements PendingWithdrawInterface
{
    public const EXPIRES_HOURS = 1;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"API"})
     * @var DateTimeImmutable
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @Groups({"API"})
     * @var Token
     */
    private $token;

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
    private $user;

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
    protected $hash;

    public function __construct(User $user, Token $token, Amount $amount, Address $address)
    {
        $this->user = $user;
        $this->token = $token;
        $this->amount = $amount->getAmount()->getAmount();
        $this->address = $address->getAddress();
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
                new Currency(MoneyWrapper::TOK_SYMBOL)
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
}
