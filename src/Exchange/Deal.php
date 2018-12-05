<?php

namespace App\Exchange;

use Money\Money;

class Deal
{
    public const SELL_SIDE = 1;
    public const BUY_SIDE = 2;
    public const MAKER_ROLE= 1;
    public const TAKER_ROLE = 2;
    
    /** @var int|null */
    private $id;
    
    /** @var float|null */
    private $timestamp;

    /** @var int|null */
    private $userId;
    
    /** @var int */
    private $side;
    
    /** @var int */
    private $role;

    /** @var Money */
    private $amount;

    /** @var Money */
    private $price;

    /** @var Money|null */
    private $deal;
    
    /** @var Money */
    private $fee;

    /** @var int|null */
    private $dealOrderId;
    
    /** @var string */
    private $market;

    public function __construct(
        ?int $id,
        ?float $timestamp,
        ?int $userId,
        int $side,
        int $role,
        Money $amount,
        Money $price,
        ?Money $deal,
        Money $fee,
        ?int $dealOrderId,
        string $market
    ) {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->userId = $userId;
        $this->side = $side;
        $this->role = $role;
        $this->amount = $amount;
        $this->price = $price;
        $this->deal = $deal;
        $this->fee = $fee;
        $this->dealOrderId = $dealOrderId;
        $this->market = $market;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getDeal(): ?Money
    {
        return $this->deal;
    }
    public function getDealOrderId(): ?int
    {
        return $this->dealOrderId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getRole(): int
    {
        return $this->role;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getSide(): int
    {
        return $this->side;
    }

    public function getFee(): Money
    {
        return $this->fee;
    }

    public function getTimestamp(): ?float
    {
        return $this->timestamp;
    }
}
