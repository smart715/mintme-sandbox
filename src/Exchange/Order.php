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

    /** @var User|null */
    private $maker;

    /** @var User|null */
    private $taker;

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

    public function __construct(
        ?int $id,
        ?User $maker,
        ?User $taker,
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
        $this->maker = $maker;
        $this->taker = $taker;
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
    public function getMaker(): ?User
    {
        return $this->maker;
    }

    /** @Groups({"Default"}) */
    public function getTaker(): ?User
    {
        return $this->taker;
    }

    public function getMakerId(): int
    {
        return $this->getMaker()->getId();
    }

    public function getTakerId(): int
    {
        return $this->getTaker()->getId();
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

    /** @Groups({"Default"}) */
    public function getMakerFirstName(): ?string
    {
        return null != $this->getMaker()
            ? $this->getMaker()->getProfile()->getFirstName()
            : null;
    }

    /** @Groups({"Default"}) */
    public function getMakerLastName(): ?string
    {
        return null != $this->getMaker()
            ? $this->getMaker()->getProfile()->getLastName()
            : null;
    }

    /** @Groups({"Default"}) */
    public function getTakerFirstName(): ?string
    {
        return null != $this->getTaker()
            ? $this->getTaker()->getProfile()->getFirstName()
            : null;
    }

    /** @Groups({"Default"}) */
    public function getTakerLastName(): ?string
    {
        return null != $this->getTaker()
            ? $this->getTaker()->getProfile()->getLastName()
            : null;
    }

    /** @Groups({"Default"}) */
    public function getTotal(): ?string
    {
        return $this->getAmount()->multiply($this->getPrice()->getAmount())->getAmount();
    }
}
