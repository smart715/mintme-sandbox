<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Token\Token;

class DeployCompletedEvent extends TokenEvent implements TokenEventInterface
{
    public function __construct(Token $token)
    {
        parent::__construct($token);
    }
}
