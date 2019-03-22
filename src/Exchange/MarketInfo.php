<?php declare(strict_types = 1);

namespace App\Exchange;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class MarketInfo
{
    /** @var Money */
    private $last;

    /** @var Money */
    private $volume;

    /** @var Money*/
    private $open;

    /** @var Money */
    private $close;

    /** @var Money */
    private $high;

    /** @var Money */
    private $low;

    /** @var string */
    private $deal;

    /** @var ?string */
    private $tokenName;

    /** @var string */
    private $cryptoSymbol;

    public function __construct(
        string $cryptoSymbol,
        ?string $tokenName,
        Money $last,
        Money $volume,
        Money $open,
        Money $close,
        Money $high,
        Money $low,
        string $deal
    ) {
        $this->cryptoSymbol = $cryptoSymbol;
        $this->tokenName = $tokenName;
        $this->last = $last;
        $this->volume = $volume;
        $this->open = $open;
        $this->close = $close;
        $this->high = $high;
        $this->low = $low;
        $this->deal = $deal;
    }

    /** @Groups({"Default", "API"}) */
    public function getLast(): Money
    {
        return $this->last;
    }

    public function getDeal(): string
    {
        return $this->deal;
    }

    public function getLow(): Money
    {
        return $this->low;
    }

    public function getHigh(): Money
    {
        return $this->high;
    }

    public function getClose(): Money
    {
        return $this->close;
    }

    /** @Groups({"Default", "API"}) */
    public function getOpen(): Money
    {
        return $this->open;
    }

    /** @Groups({"Default", "API"}) */
    public function getVolume(): Money
    {
        return $this->volume;
    }

    /** @Groups({"Default", "API"}) */
    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }

    /** @Groups({"Default", "API"}) */
    public function getTokenName(): ?string
    {
        return $this->tokenName;
    }
}
