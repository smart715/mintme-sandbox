<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class WithdrawCompletedEvent extends Event
{

    public const NAME = "withdraw.completed";

    /** @var TradebleInterface */
    protected $tradable;

    /** @var User */
    protected $user;

    /** @var string */
    protected $amount;

    public function __construct(TradebleInterface $tradable, User $user, string $amount)
    {
        $this->user = $user;
        $this->tradable = $tradable;
        $this->amount = $amount;
    }

    public function getTradable(): TradebleInterface
    {
        return $this->tradable;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }
}
