<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Config\Config;

class TraderFetcher implements TraderFetcherInterface
{
    private const PLACE_ORDER_METHOD = 'order.put_limit';
    private const CANCEL_ORDER_METHOD = 'order.cancel';
    private const FINISHED_ORDERS_METHOD = 'order.finished';
    private const PENDING_ORDERS_METHOD = 'order.pending';

    private const INSUFFICIENT_BALANCE_CODE = 10;
    private const ORDER_NOT_FOUND_CODE = 10;
    private const USER_NOT_MATCH_CODE = 11;

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var Config */
    private $config;

    public function __construct(JsonRpcInterface $jsonRpc, Config $config)
    {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
    }

    public function placeOrder(
        int $userId,
        string $tokenName,
        int $side,
        string $amount,
        string $price,
        string $takerFee,
        string $makerFee,
        int $referralId,
        string $referralFee
    ): TradeResult {
        try {
            $response = $this->jsonRpc->send(self::PLACE_ORDER_METHOD, [
                $userId + $this->config->getOffset(),
                $tokenName,
                $side,
                $amount,
                $price,
                $takerFee,
                $makerFee,
                '',
                $referralId + $this->config->getOffset(),
                $referralFee,
            ]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return self::INSUFFICIENT_BALANCE_CODE === $response->getError()['code']
                ? new TradeResult(TradeResult::INSUFFICIENT_BALANCE)
                : new TradeResult(TradeResult::FAILED);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    public function cancelOrder(int $userId, string $marketName, int $orderId): TradeResult
    {
        try {
            $response = $this->jsonRpc->send(self::CANCEL_ORDER_METHOD, [
                $userId + $this->config->getOffset(),
                $marketName,
                $orderId,
            ]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return $this->getCancelOrderErrorResult($response->getError()['code']);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    public function getFinishedOrders(
        int $userId,
        string $marketName,
        int $startTime,
        int $endTime,
        int $offset,
        int $limit,
        int $side
    ): array {
        $response = $this->jsonRpc->send(self::FINISHED_ORDERS_METHOD, [
            $userId + $this->config->getOffset(),
            $marketName,
            $startTime,
            $endTime,
            $offset,
            $limit,
            $side,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult()['records'];
    }

    public function getPendingOrders(int $userId, string $marketName, int $offset, int $limit, int $side): array
    {
        $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [
            $userId + $this->config->getOffset(),
            $marketName,
            $offset,
            $limit,
            $side,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult()['records'];
    }

    private function getCancelOrderErrorResult(int $errorCode): TradeResult
    {
        $errorMapping = [
            self::ORDER_NOT_FOUND_CODE => TradeResult::ORDER_NOT_FOUND,
            self::USER_NOT_MATCH_CODE => TradeResult::USER_NOT_MATCH,
        ];

        return array_key_exists($errorCode, $errorMapping)
            ? new TradeResult($errorMapping[$errorCode])
            : new TradeResult(TradeResult::FAILED);
    }
}
