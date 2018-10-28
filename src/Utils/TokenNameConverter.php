<?php

namespace App\Utils;

use App\Entity\Token\Token;
use Symfony\Kernel\HtttpKernel\Exception;

class TokenNameConverter implements TokenNameConverterInterface
{
    private const CRYPTO_MAP = [
        'GET_WEB' => 'WEB',
        'GET_BTC' => 'BTC'
    ];

    public function convert(Token $token): string
    {      
        if (isset(self::CRYPTO_MAP[$token->getName()])) {
            return self::CRYPTO_MAP[$token->getName()];
        }
        else {
           return 'TOK'.str_pad((string) $token->getId(), 12, '0', STR_PAD_LEFT);
        }
    }
}
