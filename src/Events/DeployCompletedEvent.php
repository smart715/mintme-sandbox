<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DeployCompletedEvent extends Event
{
    public const NAME = "deploy.completed";
    public const TYPE = "deploy";

    protected User $user;
    private string $tokenName;
    private string $txHash;

    public function __construct(User $user, string $tokenName, string $txHash)
    {
        $this->user = $user;
        $this->tokenName = $tokenName;
        $this->txHash = $txHash;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getTxHash(): string
    {
        return $this->txHash;
    }
}
