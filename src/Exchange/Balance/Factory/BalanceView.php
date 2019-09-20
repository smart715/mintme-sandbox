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
    public $exchangeble;

    /** @var bool */
    public $tradable;

    /** @var bool */
    public $deployed;

    public function __construct(
        string $identifier,
        Money $available,
        ?Money $frozen,
        string $fullname,
        ?Money $fee,
        int $subunit,
        bool $exchangeble,
        bool $tradable,
        bool $deployed
    ) {
        $this->identifier = $identifier;
        $this->available = $available;
        $this->frozen = $frozen;
        $this->fullname = $fullname;
        $this->subunit = $subunit;
        $this->fee = $fee;
        $this->exchangeble = $exchangeble;
        $this->tradable = $tradable;
        $this->deployed = $deployed;
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
    public function getSubunit(): int
    {
        return $this->subunit;
    }

    /** @Groups({"API"}) */
    public function isExchangeble(): bool
    {
        return $this->exchangeble;
    }

    /** @Groups({"API"}) */
    public function isTradable(): bool
    {
        return $this->tradable;
    }

     /** @Groups({"API"}) */
    public function isDeployed(): bool
    {
        return $this->deployed;
    }
}
