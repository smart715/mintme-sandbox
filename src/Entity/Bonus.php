<?php declare(strict_types = 1);

namespace App\Entity;

use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BonusRepository")
 * @ORM\Table(name="bonus")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Bonus
{
    public const PENDING_STATUS = 'pending';

    public const PENDING_CLAIM_STATUS = 'pending-claim';

    public const PAID_STATUS = 'paid';

    public const SIGN_UP_TYPE = 'sign-up';

    public const TOKEN_SIGN_UP_TYPE = 'token-sign-up';

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private User $user;

    /** @ORM\Column(type="string") */
    private string $status;

    /** @ORM\Column(type="string") */
    private string $quantity;

    /** @ORM\Column(type="string") */
    private string $type;

    /** @ORM\Column(type="string", length=100) */
    private string $tradableName;

    public function __construct(
        User $user,
        string $status,
        string $quantity,
        string $type,
        string $tradableName
    ) {
        $this->user = $user;
        $this->status = $status;
        $this->quantity = $quantity;
        $this->type = $type;
        $this->tradableName = $tradableName;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getQuantity(): Money
    {
        return new Money($this->quantity, new Currency(Symbols::TOK));
    }

    public function setQuantity(Money $quantity): void
    {
        $this->quantity = $quantity->getAmount();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTradableName(): string
    {
        return $this->tradableName;
    }
}
