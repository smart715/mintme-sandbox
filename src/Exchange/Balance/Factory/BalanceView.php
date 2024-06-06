<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class BalanceView
{
    public string $identifier;

    public Money $available;

    public ?Money $frozen;

    public ?Money $bonus;

    public string $fullname;

    public ?Money $fee;

    public int $subunit;

    public bool $exchangeble;

    public bool $tradable;

    public bool $deployed;

    public bool $owner;

    public bool $isBlocked;

    public bool $isToken;

    public string $cryptoSymbol;

    public bool $isCreatedOnMintmeSite;

    public bool $isRemoved;

    public bool $hasTax;

    public bool $isPausable;

    public function __construct(
        string $identifier,
        Money $available,
        ?Money $frozen,
        ?Money $bonus,
        string $fullname,
        ?Money $fee,
        int $subunit,
        bool $exchangeble,
        bool $tradable,
        bool $deployed,
        bool $owner,
        bool $isBlocked,
        bool $isToken,
        string $cryptoSymbol,
        bool $isCreatedOnMintmeSite,
        bool $isRemoved,
        bool $hasTax,
        bool $isPausable
    ) {
        $this->identifier = $identifier;
        $this->available = $available;
        $this->frozen = $frozen;
        $this->bonus = $bonus;
        $this->fullname = $fullname;
        $this->subunit = $subunit;
        $this->fee = $fee;
        $this->exchangeble = $exchangeble;
        $this->tradable = $tradable;
        $this->deployed = $deployed;
        $this->owner = $owner;
        $this->isBlocked = $isBlocked;
        $this->isToken = $isToken;
        $this->cryptoSymbol = $cryptoSymbol;
        $this->isCreatedOnMintmeSite = $isCreatedOnMintmeSite;
        $this->isRemoved = $isRemoved;
        $this->hasTax = $hasTax;
        $this->isPausable = $isPausable;
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
    public function getBonus(): ?Money
    {
        return $this->bonus;
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

    /** @Groups({"API"}) */
    public function getOwner(): bool
    {
        return $this->owner;
    }

    /** @Groups({"API"}) */
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    /** @Groups({"API"}) */
    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }

    /** @Groups({"API"}) */
    public function isCreatedOnMintmeSite(): bool
    {
        return $this->isCreatedOnMintmeSite;
    }

    /** @Groups({"API"}) */
    public function isToken(): bool
    {
        return $this->isToken;
    }

    /** @Groups({"API"}) */
    public function isRemoved(): bool
    {
        return $this->isRemoved;
    }

    /** @Groups({"API"}) */
    public function getHasTax(): bool
    {
        return $this->hasTax;
    }

    /** @Groups({"API"}) */
    public function isPausable(): bool
    {
        return $this->isPausable;
    }
}
