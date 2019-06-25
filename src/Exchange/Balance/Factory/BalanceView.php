<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class BalanceView
{
    /** @var string */
    public $identifier;

    /** @var Money */
    public $available;

    /** @var Money|null */
    public $frozen;

    /** @var string */
    public $fullname;

    /** @var Money|null */
    public $fee;

    /** @var int */
    public $subunit;

    /** @var bool */
    public $isNotExchanged;

    /** @var bool */
    public $isOwner;

    public function __construct(
        string $identifier,
        Money $available,
        ?Money $frozen,
        string $fullname,
        ?Money $fee,
        int $subunit,
        bool $isNotExchanged,
        bool $isOwner = false
    ) {
        $this->identifier = $identifier;
        $this->isNotExchanged = $isNotExchanged;
        $this->available = $available;
        $this->frozen = $frozen;
        $this->fullname = $fullname;
        $this->subunit = $subunit;
        $this->fee = $fee;
        $this->isOwner = $isOwner;
    }

    /** @Groups({"API"}) */
    public function getAvailable(): Money
    {
        return $this->available;
    }

    /** @Groups({"API"}) */
    public function getFee(): ?Money
    {
        return $this->fee;
    }

    /** @Groups({"API"}) */
    public function getFrozen(): ?Money
    {
        return $this->frozen;
    }

    /** @Groups({"API"}) */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /** @Groups({"API"}) */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /** @Groups({"API"}) */
    public function isNotExchanged(): bool
    {
        return $this->isNotExchanged;
    }

    /** @Groups({"API"}) */
    public function isOwner(): bool
    {
        return $this->isOwner;
    }

    /** @Groups({"API"}) */
    public function getSubunit(): int
    {
        return $this->subunit;
    }
}
