<?php declare(strict_types = 1);

namespace App\Exchange;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

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

    private int $orderId;

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
        int $orderId,
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
        $this->orderId = $orderId;
        $this->market = $market;
    }

    public function getDeal(): ?Money
    {
        return $this->deal;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getDealOrderId(): ?int
    {
        return $this->dealOrderId;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getRole(): int
    {
        return $this->role;
    }

    public function getMarket(): Market
    {
        return $this->market;
    }

    public function setMarket(Market $market): void
    {
        $this->market = $market;
    }
}
