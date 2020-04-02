<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;

interface MarketFactoryInterface
{
    public function create(TradebleInterface $crypto, TradebleInterface $token): ?Market;

    /** @return Market[] */
    public function createAll(): array;

    /** @return Market[] */
    public function createUserRelated(User $user): array;

    /** @return Market[] */
    public function getCoinMarkets(): array;
}
