<?php

namespace App\Exchange\Balance\Model;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class BalanceResult
{
    /**
     * @var Money
     * @Groups({"API"})
     */
    private $available;

    /** @var Money */
    private $freeze;

    /** @var bool */
    private $isFailed = false;

    private function __construct(Money $abailable, Money $freeze)
    {
        $this->available = $abailable;
        $this->freeze = $freeze;
    }

    public function getAvailable(): Money
    {
        return $this->available;
    }

    public function getFreeze(): Money
    {
        return $this->freeze;
    }

    public function isFailed(): bool
    {
        return $this->isFailed;
    }

    public static function success(Money $available, Money $freeze): self
    {
        return new self($available, $freeze);
    }

    public static function fail(string $symbol): self
    {
        $result = new self(
            new Money(0, new Currency($symbol)),
            new Money(0, new Currency($symbol))
        );

        $result->isFailed = true;

        return $result;
    }
}
