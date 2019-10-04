<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\User;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class Order extends AbstractOrder
{
    public const FINISHED_STATUS = 'finished';
    public const PENDING_STATUS = 'pending';

    /** @var User */
    private $maker;

    /** @var User|null */
    private $taker;

    /** @var string */
    private $status;

    /** @var int */
    private $referralId;

    public function __construct(
        ?int $id,
        User $maker,
        ?User $taker,
        Market $market,
        Money $amount,
        int $side,
        Money $price,
        string $status,
        ?Money $fee = null,
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
    public function getMaker(): User
    {
        return $this->maker;
    }

    /** @Groups({"Default", "API"}) */
    public function getTaker(): ?User
    {
        return $this->taker;
    }

    /** @Groups({"Default", "API"}) */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReferralId(): int
    {
        return $this->referralId;
    }

    public static function createCancelOrder(int $id, User $user, Market $market): self
    {
        return new self(
            $id,
            $user,
            null,
            $market,
            new Money('0', new Currency($market->getQuote()->getSymbol())),
            1,
            new Money('0', new Currency($market->getQuote()->getSymbol())),
            ""
        );
    }
}
