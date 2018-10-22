<?php

namespace App\Exchange;

use App\Entity\User;

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

    /** @var Market|null */
    private $market;

    /** @var string */
    private $amount;

    /** @var string */
    private $price;

    /** @var int */
    private $side;

    /** @var string */
    private $status;

    /** @var int|null */
    private $timestamp;

    public function __construct(
        ?int $id,
        int $makerId,
        ?int $takerId,
        ?Market $market,
        string $amount,
        int $side,
        string $price,
        string $status,
        ?int $timestamp = null
    ) {
        $this->id = $id;
        $this->makerId = $makerId;
        $this->takerId = $takerId;
        $this->market = $market;
        $this->amount = $amount;
        $this->side = $side;
        $this->price = $price;
        $this->status = $status;
        $this->timestamp = $timestamp;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMakerId(): int
    {
        return $this->makerId;
    }

    public function getTakerId(): ?int
    {
        return $this->takerId;
    }

    public function getMarket(): ?Market
    {
        return $this->market;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getSide(): int
    {
        return $this->side;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
