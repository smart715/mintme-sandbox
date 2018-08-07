<?php

namespace App\ValueObject;

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

    /** @var int */
    private $id;

    /** @var User */
    private $user;

    /** @var Market */
    private $market;

    /** @var string */
    private $amount;

    /** @var string */
    private $price;

    /** @var int */
    private $side;

    /** @var string */
    private $status;

    public function __construct(
        int $id,
        User $user,
        Market $market,
        string $amount,
        int $side,
        string $price,
        string $status
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->market = $market;
        $this->amount = $amount;
        $this->side = $side;
        $this->price = $price;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMarket(): Market
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
}
