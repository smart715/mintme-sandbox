<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\User;
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

    /** @var int */
    private $referralId;

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

    /** @Groups({"Default", "API"}) */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @Groups({"Default", "API"}) */
    public function getMaker(): ?User
    {
        return $this->maker;
    }

    /** @Groups({"Default", "API"}) */
    public function getTaker(): ?User
    {
        return $this->taker;
    }

    /** @Groups({"Default", "API"}) */
    public function getMarket(): Market
    {
        return $this->market;
    }

    /** @Groups({"Default", "API"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"Default", "API"}) */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /** @Groups({"Default", "API"}) */
    public function getSide(): int
    {
        return $this->side;
    }

    /** @Groups({"Default", "API"}) */
    public function getStatus(): string
    {
        return $this->status;
    }

    /** @Groups({"Default", "API"}) */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    /** @Groups({"Default", "API"}) */
    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function getReferralId(): int
    {
        return $this->referralId;
    }
}
