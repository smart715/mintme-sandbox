<?php

namespace App\Wallet\Model;

class Amount
{
    /** @var float */
    private $amount;

    public function __construct(float $amount)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Incorrect amount');
        }

        $this->amount = $amount;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
