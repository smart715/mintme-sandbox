<?php

namespace App\Wallet\Model;

use App\Entity\Crypto;
use Money\Money;

class Transaction
{
    /** @var \DateTime */
    private $date;

    /** @var string */
    private $hash;

    /** @var string|null */
    private $from;

    /** @var string */
    private $to;

    /** @var Money */
    private $amount;

    /** @var Money */
    private $fee;

    /** @var Crypto|null */
    private $crypto;

    /** @var Status */
    private $status;

    /** @var Type */
    private $type;

    public function __construct(
        \DateTime $date,
        string $hash,
        ?string $from,
        string $to,
        Money $amount,
        Money $fee,
        ?Crypto $crypto,
        Status $status,
        Type $type
    ) {
        $this->date = $date;
        $this->hash = $hash;
        $this->from = $from;
        $this->to = $to;
        $this->amount = $amount;
        $this->fee = $fee;
        $this->crypto = $crypto;
        $this->status = $status;
        $this->type = $type;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getFromAddress(): ?string
    {
        return $this->from;
    }

    public function getToAddress(): string
    {
        return $this->to;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getFee(): Money
    {
        return $this->fee;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
