<?php

namespace App\Exchange;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

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
    
    /** @var Market */
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
        Market $market
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
    public function getFee(): Money
    {
        return $this->fee;
    }

    /** @Groups({"Default"}) */
    public function getTimestamp(): ?float
    {
        return $this->timestamp;
    }
}
