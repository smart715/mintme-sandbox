<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use Money\Money;

/** @codeCoverageIgnore */
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
