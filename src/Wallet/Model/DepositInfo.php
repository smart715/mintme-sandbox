<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class DepositInfo
{
    /** @var Money */
    private $fee;

    /** @var Money|null */
    private $minDeposit;

    public function __construct(Money $fee, ?Money $minDeposit)
    {
        $this->fee = $fee;
        $this->minDeposit = $minDeposit;
    }

    /** @Groups({"API"}) */
    public function getFee(): Money
    {
        return $this->fee;
    }

    /** @Groups({"API"}) */
    public function getMinDeposit(): ?Money
    {
        return $this->minDeposit;
    }
}
