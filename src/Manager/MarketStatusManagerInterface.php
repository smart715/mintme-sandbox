<?php declare(strict_types = 1);

namespace App\Manager;

use App\Communications\Exception\FetchException;
use App\Entity\MarketStatus;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\Model\HighestPriceModel;
use App\Utils\Symbols;

interface MarketStatusManagerInterface
{
    /** @return array<string> */
    public function getFilterForTokens(): array;

    public function getMarketsCount(string $filter = '', ?string $crypto = ''): int;

    /**
     * @return array<MarketStatus|null>
     */
    public function getPredefinedMarketStatuses(): array;

    /**
     * @return array<MarketStatus|null>
     */
    public function getFilteredPromotedMarketStatuses(): array;

    /**
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @param string $order
     * @param array<string> $filters
     * @param int|null $userId
     * @param string|null $crypto
     * @param string|null $searchPhrase
     * @return array<MarketStatus|null>
     */
    public function getFilteredMarketStatuses(
        int $offset,
        int $limit,
        string $sort = "monthVolume",
        string $order = "DESC",
        array $filters = [],
        ?int $userId = null,
        ?string $crypto = Symbols::WEB,
        ?string $searchPhrase = null
    ): array;

    /**
     * @return array<MarketStatus>
     */
    public function getCryptoAndDeployedMarketsInfo(?int $offset = null, ?int $limit = null): array;

    /**
     * @param array $markets
     * @return array<MarketStatus>
     */
    public function createMarketStatus(array $markets): array;

    public function updateMarketStatus(Market $market): void;

    public function updateMarketStatusNetworks(Market $market): void;

    public function getMarketStatus(Market $market): ?MarketStatus;

    public function getOrCreateMarketStatus(Market $market): MarketStatus;

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

    /**
     * @throws FetchException
     */
    public function getTokenHighestPrice(array $markets): HighestPriceModel;
}
