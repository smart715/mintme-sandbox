<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Token\Token;
use Symfony\Contracts\EventDispatcher\Event;

class TokenEvent extends Event implements TokenEventInterface
{
    protected Token $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function getToken(): Token
    {
        return $this->token;
    }
}
