<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;

/** @codeCoverageIgnore */
class MarketEvent implements MarketEventInterface
{
    protected TokenCrypto $tokenCrypto;
    protected int $type;
    public function __construct(TokenCrypto $tokenCrypto, int $type)
    {
        $this->tokenCrypto = $tokenCrypto;
        $this->type = $type;
    }

    public function getCrypto(): Crypto
    {
        return $this->tokenCrypto->getCrypto();
    }

    public function getToken(): Token
    {
        return $this->tokenCrypto->getToken();
    }

    public function getType(): int
    {
        return $this->type;
    }
}
