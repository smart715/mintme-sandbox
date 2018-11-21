<?php

namespace App\Withdraw\Payment;

use App\Entity\Crypto;

class Transaction
{
    /** @var \DateTime */
    private $date;

    /** @var float */
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
        float $amount,
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

    public function getAmount(): float
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
