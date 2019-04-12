<?php declare(strict_types = 1);

namespace App\Entity;

use App\Wallet\Model\Amount;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PendingWithdrawRepository")
 * @ORM\Table(name="pending_withdraw")
 * @ORM\HasLifecycleCallbacks()
 */
class PendingWithdraw
{
    public const EXPIRES_HOURS = 4;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @Groups({"API"})
     * @var Crypto
     */
    private $crypto;

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
     * @ORM\Column(type="string")
     * @var string
     */
    protected $hash;

    public function __construct(User $user, Crypto $crypto, Amount $amount)
    {
        $this->user = $user;
        $this->crypto = $crypto;
        $this->amount = $amount->getAmount()->getAmount();
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

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /** @ORM\PrePersist() */
    public function init(): self
    {
        $this->hash = hash('sha256', Uuid::uuid4()->toString());
        $this->date = new DateTimeImmutable();

        return $this;
    }
}
