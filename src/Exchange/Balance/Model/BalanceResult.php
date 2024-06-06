<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class BalanceResult extends CryptoBalanceResult
{
    /**
     * @Groups({"Default", "API", "dev"})
     */
    private Money $bonus;

    private function __construct(Money $available, Money $freeze, Money $referral, ?Money $bonus = null)
    {
        parent::__construct($available, $freeze, $referral);
        $this->bonus = $bonus ?? new Money(0, $available->getCurrency());
    }

    public function getFullAvailable(): Money
    {
        return $this->getAvailable()->add($this->getBonus());
    }

    public function getBonus(): Money
    {
        return $this->bonus;
    }

    public function setBonus(Money $bonus): void
    {
        $this->bonus = $bonus;
    }

    public static function success(
        Money $available,
        Money $freeze,
        Money $referral,
        ?Money $bonus = null
    ): self {
        return new self($available, $freeze, $referral, $bonus);
    }

    public static function fail(string $symbol): self
    {
        $result = new self(
            new Money(0, new Currency($symbol)),
            new Money(0, new Currency($symbol)),
            new Money(0, new Currency($symbol))
        );

        $result->isFailed = true;

        return $result;
    }
}
