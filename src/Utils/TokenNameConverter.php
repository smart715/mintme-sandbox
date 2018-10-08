<?php

namespace App\Utils;

use App\Entity\Token;

class TokenNameConverter implements TokenNameConverterInterface
{
    public function convert(Token $token): string
    {
        return 'TOK'.str_pad((string) $token->getId(), 12, '0', STR_PAD_LEFT);
    }
}