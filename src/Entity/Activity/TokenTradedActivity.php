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
class TokenTradedActivity extends Activity
{
    /** @ORM\Column(type="string") */
    protected string $amount;

    /** @ORM\Column(type="string") */
    protected string $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user1_id")
     */
    protected User $buyer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user2_id")
     */
    protected User $seller;

    public function getType(): int
    {
        return self::TOKEN_TRADED;
    }

    public function setBuyer(User $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getBuyer(): User
    {
        return $this->buyer;
    }

    public function setSeller(User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
