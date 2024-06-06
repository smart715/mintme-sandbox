<?php declare(strict_types = 1);

namespace App\Manager\Model;

use App\Entity\Crypto;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TradableNetworkModel
{
    private string $networkName;
    private Crypto $networkInfo;
    private Money $fee;
    private int $subunit;
    private ?string $address;
    private bool $isDefault;

    public function __construct(
        string $networkName,
        Crypto $networkInfo,
        Money $fee,
        int $subunit,
        ?string $address = null,
        bool $isDefault = false
    ) {
        $this->networkName = $networkName;
        $this->networkInfo = $networkInfo;
        $this->fee = $fee;
        $this->subunit = $subunit;
        $this->address = $address;
        $this->isDefault = $isDefault;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getNetworkName(): string
    {
        return $this->networkName;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getNetworkInfo(): Crypto
    {
        return $this->networkInfo;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getFee(): Money
    {
        return $this->fee;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getFeeCurrency(): Currency
    {
        return $this->fee->getCurrency();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSubunit(): ?int
    {
        return $this->subunit;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }
}
