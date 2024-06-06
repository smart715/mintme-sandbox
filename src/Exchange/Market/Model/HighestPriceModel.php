<?php declare(strict_types = 1);

namespace App\Exchange\Market\Model;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class HighestPriceModel
{
    private string $symbol;
    private string $value;
    private int $subunit;
    private string $valueInUsd;
    private string $open;

    public function __construct(string $symbol, string $value, int $subunit, string $valueInUsd, string $open)
    {
        $this->symbol = $symbol;
        $this->value = $value;
        $this->subunit = $subunit;
        $this->valueInUsd = $valueInUsd;
        $this->open = $open;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSubunit(): int
    {
        return $this->subunit;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getValueInUsd(): string
    {
        return $this->valueInUsd;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getOpen(): string
    {
        return $this->open;
    }
}
