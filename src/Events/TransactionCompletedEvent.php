<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class TransactionCompletedEvent extends Event implements TransactionCompletedEventInterface
{
    public const NAME = "transaction.completed";
    public const TYPE = "transaction";

    protected TradebleInterface $tradable;
    protected User $user;
    protected string $amount;

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
