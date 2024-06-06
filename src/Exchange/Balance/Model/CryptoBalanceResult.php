<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class CryptoBalanceResult
{
    /**
     * @Groups({"Default", "API", "dev"})
     */
    protected Money $available;

    /**
     * @Groups({"Default", "API", "dev"})
     */
    protected Money $freeze;

    protected Money $referral;

    protected bool $isFailed = false; // phpcs:ignore

    protected function __construct(Money $available, Money $freeze, Money $referral)
    {
        $this->available = $available;
        $this->freeze = $freeze;
        $this->referral = $referral;
    }

    public function getAvailable(): Money
    {
        return $this->available;
    }

    public function getFullAvailable(): Money
    {
        return $this->getAvailable();
    }

    public function getFreeze(): Money
    {
        return $this->freeze;
    }

    public function getReferral(): Money
    {
        return $this->referral;
    }

    public function isFailed(): bool
    {
        return $this->isFailed;
    }

    public static function success(
        Money $available,
        Money $freeze,
        Money $referral
    ): self {
        return new self($available, $freeze, $referral);
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
