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
    public function getCurrencySymbol(): string
    {
        return $this->crypto->getSymbol();
    }

    /** @Groups({"Default", "API"}) */
    public function getToken(): ?Token
    {
        return $this->token;
    }
}
