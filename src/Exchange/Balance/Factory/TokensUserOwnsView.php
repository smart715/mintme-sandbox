<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Image;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TokensUserOwnsView
{
    public string $name;

    public Money $available;

    public int $subunit;

    public ?Image $avatar;

    public string $cryptoSymbol;

    public bool $showDeployedIcon;

    public ?int $rank;

    public function __construct(
        string $name,
        Money $available,
        int $subunit,
        ?Image $avatar,
        string $cryptoSymbol,
        bool $showDeployedIcon,
        ?int $rank
    ) {
        $this->name = $name;
        $this->available = $available;
        $this->subunit = $subunit;
        $this->avatar = $avatar;
        $this->cryptoSymbol = $cryptoSymbol;
        $this->showDeployedIcon = $showDeployedIcon;
        $this->rank = $rank;
    }

    /** @Groups({"API"}) */
    public function getName(): string
    {
        return $this->name;
    }

    /** @Groups({"API"}) */
    public function getAvailable(): Money
    {
        return $this->available;
    }

    /** @Groups({"API"}) */
    public function getSubunit(): int
    {
        return $this->subunit;
    }

    /** @Groups({"API"}) */
    public function getAvatar(): ?Image
    {
        return $this->avatar;
    }

    /** @Groups({"API"}) */
    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }

    /** @Groups({"API"}) */
    public function getShowDeployedIcon(): bool
    {
        return $this->showDeployedIcon;
    }

    /** @Groups({"API"}) */
    public function getRank(): ?int
    {
        return $this->rank;
    }
}
