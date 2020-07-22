<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\User;
use App\Exchange\Market;

interface MarketStatusManagerInterface
{
    public function getMarketsCount(int $deployed = 0): int;

    public function getUserRelatedMarketsCount(int $userId): int;

    /** @return array<MarketStatus> */
    public function getMarketsInfo(
        int $offset,
        int $limit,
        string $sort = "monthVolume",
        string $order = "DESC",
        int $deployed = 0,
        ?int $userId = null
    ): array;

    /**
     * @param string $parse
     * @return array<MarketStatus>
     */
    public function getAllMarketsInfo(string $parse): array;

    /** @var Market[] */
    public function createMarketStatus(array $market): void;

    public function updateMarketStatus(Market $market): void;

    public function getMarketStatus(Market $market): ?MarketStatus;

    public function getUserMarketStatus(User $user, int $offset, int $limit, bool $deployed = false): array;
}
