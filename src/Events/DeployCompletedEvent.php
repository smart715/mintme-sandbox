<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Token\Token;
use App\Entity\User;

class DeployCompletedEvent extends TokenEvent implements TokenEventInterface, UserEventInterface
{
    protected User $user;
    private string $txHash;

    public function __construct(Token $token, string $txHash)
    {
        $this->user = $token->getProfile()->getUser();
        $this->txHash = $txHash;

        parent::__construct($token);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTxHash(): string
    {
        return $this->txHash;
    }
}
