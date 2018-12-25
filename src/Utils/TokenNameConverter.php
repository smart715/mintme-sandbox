<?php

namespace App\Utils;

use App\Entity\Token\Token;
use App\Manager\CryptoManagerInterface;

class TokenNameConverter implements TokenNameConverterInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(CryptoManagerInterface $cryptoManager)
    {
        $this->cryptoManager = $cryptoManager;
    }

    public function convert(Token $token): string
    {
        return !$this->cryptoManager->findBySymbol(strtoupper($token->getName() ?? ''))
            ? 'TOK'.str_pad((string)$token->getId(), 12, '0', STR_PAD_LEFT)
            : (string)$token->getName();
    }
}
