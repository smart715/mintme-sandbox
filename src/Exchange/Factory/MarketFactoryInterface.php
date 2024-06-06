<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Market;

interface MarketFactoryInterface
{
    public function createBySymbols(string $baseSymbol, string $quoteSymbol): Market;

    public function create(TradableInterface $crypto, TradableInterface $token): Market;

    /** @return Market[] */
    public function createAll(): array;

    /**
     * @param User $user
     * @param bool $deployed
     * @return Market[]
     */
    public function createUserRelated(User $user, bool $deployed = false): array;

    /**
     * @param Token $token
     * @return Market[]
     */
    public function createTokenMarkets(Token $token): array;

    /** @return Market[] */
    public function getCoinMarkets(): array;

    /** @return Market[] */
    public function getMintMeCoinMarkets(): array;
}
