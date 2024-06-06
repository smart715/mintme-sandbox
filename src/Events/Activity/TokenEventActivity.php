<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\Token\Token;
use App\Events\TokenEvent;

/** @codeCoverageIgnore */
class TokenEventActivity extends TokenEvent implements ActivityEventInterface
{
    public const NAME = 'token.activity';
    protected int $type;

    public function __construct(Token $token, int $type)
    {
        $this->type = $type;

        parent::__construct($token);
    }

    public function getType(): int
    {
        return $this->type;
    }
}
