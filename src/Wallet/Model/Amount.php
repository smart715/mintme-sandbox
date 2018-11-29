<?php

namespace App\Wallet\Model;

use Money\Money;

class Amount
{
    /** @var Money */
    private $amount;

    public function __construct(Money $amount)
    {
        if ($amount->isNegative() || $amount->isZero()) {
            throw new \InvalidArgumentException('Incorrect amount');
        }

        $this->amount = $amount;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}
