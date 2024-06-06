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
     * @SWG\Property(type="number")
     */
    private Money $last;

    /**
     * @SWG\Property(type="number")
     */
    private Money $volume;

    /**
     * @SWG\Property(type="number")
     */
    private Money $open;

    /**
     * @SWG\Property(type="number")
     */
    private Money $close;

    /**
     * @SWG\Property(type="number")
     */
    private Money $high;

    /**
     * @SWG\Property(type="number")
     */
    private Money $low;

    /**
     * @SWG\Property(type="number")
     */
    private Money $deal;

    /**
     * @SWG\Property(property="quote")
     */
    private string $tokenName;

    private Money $monthDeal;

    /**
     * @SWG\Property(property="base")
     */
    private string $cryptoSymbol;

    /**
     * @SWG\Property(type="number")
     */
    private Money $buyDepth;

    private Money $soldOnMarket;

    private Money $volumeDonation;

    /** @var \DateTimeImmutable|null */
    private $expires;

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
        Money $monthDeal,
        Money $buyDepth,
        Money $soldOnMarket,
        Money $volumeDonation,
        ?\DateTimeImmutable $expires
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
        $this->buyDepth = $buyDepth;
        $this->soldOnMarket = $soldOnMarket;
        $this->volumeDonation = $volumeDonation;
        $this->expires = $expires;
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

    public function setCryptoSymbol(string $cryptoSymbol): void
    {
        $this->cryptoSymbol = $cryptoSymbol;
    }

    /**
     * @SerializedName("quote")
     * @Groups({"Default", "API", "dev"})
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function setTokenName(string $tokenName): void
    {
        $this->tokenName = $tokenName;
    }

    /** @Groups({"dev"}) */
    public function getBuyDepth(): Money
    {
        return $this->buyDepth;
    }

    /** @Groups({"Default"}) */
    public function getSoldOnMarket(): Money
    {
        return $this->soldOnMarket;
    }

    public function getVolumeDonation(): Money
    {
        return $this->volumeDonation;
    }

    public function getExpires(): ?\DateTimeImmutable
    {
        return $this->expires;
    }
}
