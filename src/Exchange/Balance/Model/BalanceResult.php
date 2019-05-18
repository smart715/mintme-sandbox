<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class BalanceResult
{
    /**
     * @var Money
     * @Groups({"Default", "API"})
     */
    private $available;

    /** @var Money */
    private $freeze;

    /** @var Money */
    private $referral;

    /** @var bool */
    private $isFailed = false;

    private function __construct(Money $available, Money $freeze, Money $referral)
    {
        $this->available = $available;
        $this->freeze = $freeze;
        $this->referral = $referral;
    }

    public function getAvailable(): Money
    {
        return $this->available;
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

    public static function success(Money $available, Money $freeze, Money $referral): self
    {
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
