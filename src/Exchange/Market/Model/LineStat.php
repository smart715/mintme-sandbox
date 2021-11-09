<?php declare(strict_types = 1);

namespace App\Exchange\Market\Model;

use App\Exchange\Market;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class LineStat
{
    /** @var \DateTimeImmutable */
    private $time;

    /** @var Money */
    private $open;

    /** @var Money */
    private $close;

    /** @var Money */
    private $highest;

    /** @var Money */
    private $lowest;

    /** @var Money */
    private $volume;

    /** @var Money */
    private $amount;

    /** @var Market */
    private $market;

    public function __construct(
        \DateTimeImmutable $time,
        Money $open,
        Money $close,
        Money $highest,
        Money $lowest,
        Money $volume,
        Money $amount,
        Market $market
    ) {
        $this->time = $time;
        $this->open = $open;
        $this->close = $close;
        $this->highest = $highest;
        $this->lowest = $lowest;
        $this->volume = $volume;
        $this->amount = $amount;
        $this->market = $market;
    }

    /** @Groups({"Default", "API"}) */
    public function getTime(): int
    {
        return $this->time->getTimestamp();
    }

    /** @Groups({"Default", "API"}) */
    public function getOpen(): Money
    {
        return $this->open;
    }

    /** @Groups({"Default", "API"}) */
    public function getClose(): Money
    {
        return $this->close;
    }

    /** @Groups({"Default", "API"}) */
    public function getHighest(): Money
    {
        return $this->highest;
    }

    /** @Groups({"Default", "API"}) */
    public function getLowest(): Money
    {
        return $this->lowest;
    }

    /** @Groups({"Default", "API"}) */
    public function getVolume(): Money
    {
        return $this->volume;
    }

    /** @Groups({"Default", "API"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"Default", "API"}) */
    public function getMarket(): Market
    {
        return $this->market;
    }
}
