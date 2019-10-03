<?php declare(strict_types = 1);

namespace App\Exchange;

use Money\Money;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @codeCoverageIgnore
 */
class MarketInfo
{
    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $last;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $volume;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $open;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $close;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $high;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $low;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    private $deal;

    /**
     * @SWG\Property(property="quote")
     * @var string
     */
    private $tokenName;

    /** @var Money */
    private $monthDeal;

    /**
     * @SWG\Property(property="base")
     * @var string
     */
    private $cryptoSymbol;

    public function __construct(
        string $cryptoSymbol,
        string $tokenName,
        Money $last,
        Money $volume,
        Money $open,
        Money $close,
        Money $high,
        Money $low,
        Money $deal,
        Money $monthDeal
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
        $this->monthDeal = $monthDeal;
    }

    /**
     * @Groups({"Default", "API", "dev"})
     * @SWG\Property(type="string")
     */
    public function getLast(): Money
    {
        return $this->last;
    }

    /** @Groups({"dev"}) */
    public function getDeal(): Money
    {
        return $this->deal;
    }

    /** @Groups({"dev"}) */
    public function getLow(): Money
    {
        return $this->low;
    }

    public function getMonthDeal(): Money
    {
        return $this->monthDeal;
    }

    /** @Groups({"dev"}) */
    public function getHigh(): Money
    {
        return $this->high;
    }

    /** @Groups({"dev"}) */
    public function getClose(): Money
    {
        return $this->close;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getOpen(): Money
    {
        return $this->open;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getVolume(): Money
    {
        return $this->volume;
    }

    /**
     * @SerializedName("base")
     * @Groups({"Default", "API", "dev"})
     */
    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }

    /**
     * @SerializedName("quote")
     * @Groups({"Default", "API", "dev"})
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }
}
