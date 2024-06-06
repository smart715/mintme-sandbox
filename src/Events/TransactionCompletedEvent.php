<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradableInterface;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class TransactionCompletedEvent extends Event implements TransactionCompletedEventInterface
{
    public const NAME = "transaction.completed";
    public const TYPE = "transaction";

    protected TradableInterface $tradable;
    protected User $user;
    protected string $amount;
    protected string $address;
    protected int $type;
    protected string $cryptoNetworkName;


    public function __construct(
        TradableInterface $tradable,
        User $user,
        string $amount,
        string $address,
        string $cryptoNetworkName,
        int $type
    ) {
        $this->user = $user;
        $this->tradable = $tradable;
        $this->amount = $amount;
        $this->address = $address;
        $this->type = $type;
        $this->cryptoNetworkName = $cryptoNetworkName;
    }

    public function getTradable(): TradableInterface
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getCryptoNetworkName(): string
    {
        return $this->cryptoNetworkName;
    }
}
