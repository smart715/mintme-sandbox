<?php declare(strict_types = 1);

namespace App\Exchange;

use Money\Money;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractOrder
{
    public const ALL_SIDE = 0;
    public const SELL_SIDE = 1;
    public const BUY_SIDE = 2;

    public const SIDE_MAP = [
        'all' => self::ALL_SIDE,
        'sell' => self::SELL_SIDE,
        'buy' => self::BUY_SIDE,
    ];

    /** @var int|null */
    protected $id;

    /** @var int|null */
    protected $timestamp;

    /**
     * @var int
     * @SWG\Property(description="1 - sell, 2 - buy")
     */
    protected $side;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    protected $amount;

    /**
     * @var Money
     * @SWG\Property(type="number")
     */
    protected $price;

    /**
     * @var ?Money
     * @SWG\Property(type="number")
     */
    protected $fee;

    /**
     * @var Market
     * @SWG\Property(ref="#/definitions/Market")
     */
    protected $market;

    /** @Groups({"Default", "API", "dev"}) */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getMarket(): Market
    {
        return $this->market;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getSide(): int
    {
        return $this->side;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getFee(): ?Money
    {
        return $this->fee;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
