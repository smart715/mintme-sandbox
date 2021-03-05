<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\User;
use App\Exchange\Market;

interface MarketStatusManagerInterface
{
    public function getMarketsCount(int $deployed = 0): int;

    public function getUserRelatedMarketsCount(int $userId): int;

    /**
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @param string $order
     * @param int $filter
     * @param int|null $userId
     * @return array<MarketStatus>
     */
    public function getMarketsInfo(
        int $offset,
        int $limit,
        string $sort = "monthVolume",
        string $order = "DESC",
        int $filter = 0,
        ?int $userId = null
    ): array;

    /**
     * @return array<MarketStatus>
     */
    public function getCryptoAndDeployedMarketsInfo(?int $offset = null, ?int $limit = null): array;

    /**
     * @param array $market
     */
    public function createMarketStatus(array $market): void;

    public function updateMarketStatus(Market $market): void;

    public function getMarketStatus(Market $market): ?MarketStatus;

    /**
     * @param User $user
     * @param int $offset
     * @param int $limit
     * @param bool $deployed
     * @return array
     */
    public function getUserMarketStatus(User $user, int $offset, int $limit, bool $deployed = false): array;

    public function isValid(Market $market, bool $reverseBaseQuote = false): bool;

    /**
     * @return array
     */
    public function getExpired(): array;
}
