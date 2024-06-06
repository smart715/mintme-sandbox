<?php declare(strict_types = 1);

namespace App\Exchange\Market;

interface MarketFetcherInterface
{
    public function getPendingOrders(string $market, int $offset, int $limit, int $side): array;
    public function getPendingOrder(string $market, int $id): array;
    public function getMarketInfo(string $market, int $period = 86400): array;
    public function getExecutedOrders(string $market, int $offset = 0, int $limit = 100): array;
    public function getUserExecutedHistory(
        int $userId,
        string $market,
        int $offset = 0,
        int $limit = 100,
        int $startTimestamp = 0,
        int $endTimestamp = 0,
        bool $skipDonations = true
    ): array;
    public function getPendingOrdersByUser(int $userId, string $market, int $offset = 0, int $limit = 100): array;
    public function getKLineStat(string $market, int $start, int $end, int $interval): array;
    public function getSummary(array $markets): array;
    public function getMarketAnalytics(int $from, int $to): array;
}
