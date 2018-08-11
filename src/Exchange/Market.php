<?php

namespace App\Exchange;

use App\Entity\Crypto;
use App\Entity\Token;

class Market
{
    /** @var Crypto */
    private $crypto;

    /** @var Token */
    private $token;

    public function __construct(Crypto $crypto, Token $token)
    {
        $this->crypto = $crypto;
        $this->token = $token;
    }

    public function getHiddenName(): string
    {
        $cryptoSymbol = strtoupper($this->crypto->getSymbol());
        $tokenName = 'TOK'.str_pad((string) $this->token->getId(), 12, '0', STR_PAD_LEFT);

        return $tokenName.$cryptoSymbol;
    }

    public function getCurrencySymbol(): string
    {
        return $this->crypto->getSymbol();
    }

    public function getTokenName(): string
    {
        return $this->token->getName();
    }
}
