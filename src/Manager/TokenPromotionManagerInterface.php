<?php declare(strict_types = 1);

namespace App\Manager;

use App\Communications\Exception\FetchException;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenPromotion;
use App\Exchange\Balance\Exception\BalanceException;

interface TokenPromotionManagerInterface
{
    /** @return TokenPromotion[] */
    public function findActivePromotionsByToken(Token $token): array;

    /** @return TokenPromotion[] */
    public function findActivePromotions(): array;

    /**
     * @throws \Throwable|BalanceException|FetchException
     */
    public function buyPromotion(Token $token, array $tariff, Crypto $payCrypto): TokenPromotion;
}
