<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;

interface MarketFactoryInterface
{
    public function create(TradebleInterface $crypto, TradebleInterface $token): Market;

    /** @return Market[] */
    public function createAll(): array;

    /**
     * @param User $user
     * @param bool $deployed
     * @return Market[]
     */
    public function createUserRelated(User $user, bool $deployed = false): array;

    /** @return Market[] */
    public function getCoinMarkets(): array;
}
