<?php

namespace App\Withdraw\Payment;

use App\Entity\Crypto;
use Money\Money;

class Transaction
{
    /** @var \DateTime */
    private $date;

    /** @var Money */
    private $amount;

    /** @var float */
    private $fee;

    /** @var string */
    private $hash;

    /** @var Status */
    private $status;

    /** @var string */
    private $address;

    /** @var Crypto|null */
    private $crypto;

    public function __construct(
        \DateTime $date,
        Money $amount,
        float $fee,
        Status $status,
        string $hash,
        string $address,
        ?Crypto $crypto
    ) {
        $this->date = $date;
        $this->amount = $amount;
        $this->fee = $fee;
        $this->hash = $hash;
        $this->status = $status;
        $this->address = $address;
        $this->crypto = $crypto;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getFee(): float
    {
        return $this->fee;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }
}
