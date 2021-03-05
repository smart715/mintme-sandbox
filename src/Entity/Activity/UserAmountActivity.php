<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
abstract class UserAmountActivity extends Activity
{
    /** @ORM\Column(type="string") */
    protected string $amount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user1_id")
     */
    protected User $user;

    protected string $amount_symbol = Symbols::USD; // phpcs:ignore

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->amount_symbol));
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getUser(): User
    {
        return $this->user;
    }
}
