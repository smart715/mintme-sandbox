<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class WithdrawInfo
{
    private Money $minFee;
    private ?bool $isPaused;

    public function __construct(Money $minFee, ?bool $isPaused)
    {
        $this->minFee = $minFee;
        $this->isPaused = $isPaused;
    }

    /** @Groups({"API"}) */
    public function getMinFee(): Money
    {
        return $this->minFee;
    }

    /** @Groups({"API"}) */
    public function isPaused(): ?bool
    {
        return $this->isPaused;
    }
}
