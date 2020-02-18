<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\User;
use App\Exchange\Market;

interface MarketStatusManagerInterface
{
    public function getMarketsCount(): int;

    /** @return array<MarketStatus> */
    public function getMarketsInfo(int $offset, int $limit): array;

    /** @return array<MarketStatus> */
    public function getAllMarketsInfo(): array;

    /** @var Market[] */
    public function createMarketStatus(array $market): void;

    public function updateMarketStatus(Market $market): void;

    /** @return array */
    public function getUserMarketStatus(User $user, int $offset, int $limit): array;
}
