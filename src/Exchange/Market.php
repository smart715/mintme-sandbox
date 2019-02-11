<?php

namespace App\Exchange;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use Symfony\Component\Serializer\Annotation\Groups;

class Market
{
    /** @var Crypto|null */
    private $crypto;

    /** @var Token|null */
    private $token;

    public function __construct(?Crypto $crypto, ?Token $token)
    {
        $this->crypto = $crypto;
        $this->token = $token;
    }

    /** @Groups({"Default", "API"}) */
    public function getHiddenName(): string
    {
        $cryptoSymbol = strtoupper($this->crypto->getSymbol());
        $tokenName = 'TOK'.str_pad((string) $this->token->getId(), 12, '0', STR_PAD_LEFT);

        return $tokenName.$cryptoSymbol;
    }

    /** @Groups({"Default", "API"}) */
    public function getCurrencySymbol(): string
    {
        return $this->crypto->getSymbol();
    }

    /** @Groups({"Default", "API"}) */
    public function getTokenName(): ?string
    {
        return $this->token->getName();
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }
}
