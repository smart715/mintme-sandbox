<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use Money\Money;

class Fee
{
    /** @var Money */
    private $fee;

    public function __construct(Money $fee)
    {
        if ($fee->isNegative() || $fee->isZero()) {
            throw new \InvalidArgumentException('Incorrect fee');
        }

        $this->fee = $fee;
    }

    public function getFee(): Money
    {
        return $this->fee;
    }
}
