<?php

namespace App\Exchange;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

class Order
{
    public const ALL_SIDE = 0;
    public const SELL_SIDE = 1;
    public const BUY_SIDE = 2;

    public const SIDE_MAP = [
        'all' => self::ALL_SIDE,
        'sell' => self::SELL_SIDE,
        'buy' => self::BUY_SIDE,
    ];

    public const FINISHED_STATUS = 'finished';
    public const PENDING_STATUS = 'pending';

    /** @var int|null */
    private $id;

    /** @var int */
    private $makerId;

    /** @var int|null */
    private $takerId;

    /** @var Market */
    private $market;

    /** @var Money */
    private $amount;

    /** @var Money */
    private $price;

    /** @var int */
    private $side;

    /** @var string */
    private $status;

    /** @var float|null */
    private $fee;

    /** @var int|null */
    private $timestamp;

    /** @var int */
    private $referralId;

    public function __construct(
        ?int $id,
        int $makerId,
        ?int $takerId,
        Market $market,
        Money $amount,
        int $side,
        Money $price,
        string $status,
        ?float $fee = null,
        ?int $timestamp = null,
        int $referralId = 0
    ) {
        $this->id = $id;
        $this->makerId = $makerId;
        $this->takerId = $takerId;
        $this->market = $market;
        $this->amount = $amount;
        $this->side = $side;
        $this->price = $price;
        $this->status = $status;
        $this->fee = $fee;
        $this->timestamp = $timestamp;
        $this->referralId = $referralId;
    }

    /** @Groups({"Default"}) */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @Groups({"Default"}) */
    public function getMakerId(): int
    {
        return $this->makerId;
    }

    /** @Groups({"Default"}) */
    public function getTakerId(): ?int
    {
        return $this->takerId;
    }

    /** @Groups({"Default"}) */
    public function getMarket(): Market
    {
        return $this->market;
    }

    /** @Groups({"Default"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"Default"}) */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /** @Groups({"Default"}) */
    public function getSide(): int
    {
        return $this->side;
    }

    /** @Groups({"Default"}) */
    public function getStatus(): string
    {
        return $this->status;
    }

    /** @Groups({"Default"}) */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    /** @Groups({"Default"}) */
    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function getReferralId(): int
    {
        return $this->referralId;
    }
}
