<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Market\Model\BuyOrdersSummaryResult;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Exchange\Market\Model\Summary;
use App\Exchange\MarketInfo;
use App\Exchange\Order;
use App\Exchange\Trade\CheckTradeResult;
use Money\Money;

interface MarketHandlerInterface
{
    /**
     * @param Market $market
     * @param int $offset
     * @param int $limit
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    public function getPendingSellOrders(
        Market $market,
        int $offset = 0,
        int $limit = 50,
        bool $reverseBaseQuote = false
    ): array;

    /**
     * @param Market $market
     * @return Order[]
     */
    public function getAllPendingBuyOrders(Market $market): array;

    /**
     * @param Market $market
     * @return Order[]
     */
    public function getAllPendingSellOrders(Market $market): array;

    /**
     * @param Market $market
     * @param int $offset
     * @param int $limit
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    public function getPendingBuyOrders(
        Market $market,
        int $offset = 0,
        int $limit = 50,
        bool $reverseBaseQuote = false
    ): array;

    /**
     * @param Market $market
     * @param int $lastId
     * @param int $limit
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    public function getExecutedOrders(
        Market $market,
        int $lastId = 0,
        int $limit = 50,
        bool $reverseBaseQuote = false
    ): array;

    /**
     * @param User $userId
     * @param Market[] $markets
     * @param int $offset
     * @param int $limit
     * @param bool $reverseBaseQuote
     * @return Deal[]
     */
    public function getUserExecutedHistory(
        User $userId,
        array $markets,
        int $offset = 0,
        int $limit = 50,
        bool $reverseBaseQuote = false,
        int $donationsOffset = 0,
        int $fullDonationsOffset = 0
    ): array;

    /**
     * @param User $user
     * @param Market[] $markets
     * @param int $offset
     * @param int $limit
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    public function getPendingOrdersByUser(
        User $user,
        array $markets,
        int $offset = 0,
        int $limit = 50,
        bool $reverseBaseQuote = false
    ): array;

    public function getExpectedSellResult(Market $market, string $amount, string $feeRate): CheckTradeResult;

    public function getExpectedSellReversedResult(Market $market, string $amountToReceive, string $feeRate): CheckTradeResult;

    public function getExpectedBuyResult(Market $market, string $amount, string $feeRate): CheckTradeResult;

    public function getExpectedBuyReversedResult(Market $market, string $amountToReceive, string $feeRate): CheckTradeResult;

    public function getExpectedDonationReversedResult(Market $market, string $amountToReceive, string $feeRate): CheckTradeResult;

    public function getMarketInfo(Market $market, int $period = 86400): MarketInfo;

    /**
     * @param Market[] $market
     * @return Summary[]
     */
    public function getSummary(array $market): array;

    public function getOneSummary(Market $market): Summary;

    /**
     * @param Market $market
     * @return Market\Model\LineStat[]
     */
    public function getKLineStatDaily(Market $market): array;

    /**
     * @param Market $market
     * @return Market\Model\LineStat[]
     */
    public function getKLineStatByPeriod(Market $market, string $period): array;

    public function getExecutedOrder(Market $market, int $id, int $limit = 100): ?Order;

    public function getPendingOrder(Market $market, int $id): ?Order;

    public function getBuyDepth(Market $market): string;

    public function getSellOrdersSummary(Market $market, ?User $user = null): SellOrdersSummaryResult;

    public function getBuyOrdersSummary(Market $market): BuyOrdersSummaryResult;

    public function getSellOrdersSummaryByUser(User $user, Market $market): array;

    public function getTokenSellOrdersSummary(Token $token, User $user): string;

    /**
     * @param Market $market
     * @param int $period
     * @return array
     */
    public function getMarketStatus(Market $market, int $period = 86400): array;

    public function soldOnMarket(Token $token): Money;

    /**
     * @param array $result
     * @param array $markets
     * @param bool $reverseBaseQuote
     * @return Deal[]
     */
    public function parseDeals(
        array $result,
        array $markets,
        bool $reverseBaseQuote = false,
        bool $limitedResult = true
    ): array;

    /**
     * @param array $result
     * @param Market $market
     * @param bool $limitedResult
     * @return Deal[]
     */
    public function parseDealsSingleMarket(
        array $result,
        Market $market,
        bool $limitedResult = true
    ): array;
}
