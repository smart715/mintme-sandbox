<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Entity\Crypto;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
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

    /** @Groups({"API"}) */
    public function getHash(): string
    {
        return $this->hash;
    }

    /** @Groups({"API"}) */
    public function getFromAddress(): ?string
    {
        return $this->from;
    }

    /** @Groups({"API"}) */
    public function getToAddress(): string
    {
        return $this->to;
    }

    /** @Groups({"API"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"API"}) */
    public function getFee(): Money
    {
        return $this->fee;
    }

    /** @Groups({"API"}) */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /** @Groups({"API"}) */
    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }

    /** @Groups({"API"}) */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /** @Groups({"API"}) */
    public function getType(): Type
    {
        return $this->type;
    }
}
