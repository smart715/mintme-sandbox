<?php declare(strict_types = 1);

namespace App\Exchange;

use Money\Money;

/** @codeCoverageIgnore */
class Deal extends AbstractOrder
{
    public const MAKER_ROLE= 1;
    public const TAKER_ROLE = 2;

    /** @var int|null */
    private $userId;
    
    /** @var int */
    private $role;

    /** @var Money|null */
    private $deal;

    /** @var int|null */
    private $dealOrderId;

    public function __construct(
        ?int $id,
        ?int $timestamp,
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
}
