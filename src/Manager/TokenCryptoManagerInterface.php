<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;

interface TokenCryptoManagerInterface
{
    public function createTokenCrypto(Crypto $payCrypto, Crypto $marketCrypto, Token $token): void;

    public function getByCryptoAndToken(Crypto $crypto, Token $token): ?TokenCrypto;
    public function getTotalCostPerCrypto(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;
}
