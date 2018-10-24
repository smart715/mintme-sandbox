<?php

namespace App\Utils;

use App\Entity\Token\Token;

class TokenNameConverter implements TokenNameConverterInterface
{
    public function convert(Token $token): string
    {
        return "WEB" === $token->getName() ?  "WEB" : 'TOK'.str_pad((string) $token->getId(), 12, '0', STR_PAD_LEFT);
    }
}
