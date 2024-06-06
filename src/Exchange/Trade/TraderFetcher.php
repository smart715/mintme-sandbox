<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Config\Config;
use App\Services\TranslatorService\TranslatorInterface;

class TraderFetcher implements TraderFetcherInterface
{
    private const PLACE_ORDER_METHOD = 'order.put_limit';
    private const PUT_ORDER_METHOD = 'order.put_market';
    private const CANCEL_ORDER_METHOD = 'order.cancel';
    private const FINISHED_ORDERS_METHOD = 'order.finished';
    private const PENDING_ORDERS_METHOD = 'order.pending';
    private const FINISHED_ORDER_METHOD_DETAILS = 'order.finished_detail';
    private const PENDING_ORDER_METHOD_DETAILS = 'order.pending_detail';
    private const ORDER_DETAILS_METHOD = 'order.deals';

    private const INSUFFICIENT_BALANCE_CODE = 10;
    private const ORDER_NOT_FOUND_CODE = 10;
    private const USER_NOT_MATCH_CODE = 11;
    private const SMALL_AMOUNT_CODE = 11;
    private const NO_ENOUGH_TRADER_CODE = 11;

    private JsonRpcInterface $jsonRpc;
    private Config $config;
    private TranslatorInterface $translator;

    public function __construct(JsonRpcInterface $jsonRpc, Config $config, TranslatorInterface $translator)
    {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
        $this->translator = $translator;
    }

    public function executeOrder(
        int $userId,
        string $market,
        int $side,
        string $amount,
        string $fee,
        int $referralId,
        string $referralFee
    ): TradeResult {
        try {
            $response = $this->jsonRpc->send(self::PUT_ORDER_METHOD, [
                $userId + $this->config->getOffset(),
                $market,
                $side,
                $amount,
                $fee,
                '',
                $referralId + $this->config->getOffset(),
                $referralFee,
            ]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED, $this->translator);
        }

        if ($response->hasError()) {
            return $this->getExecuteOrderErrorResult($response->getError()['code']);
        }

        return new TradeResult(TradeResult::SUCCESS, $this->translator, null, $response->getResult()['id']);
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
    ): PlaceOrderResult {
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
            return new PlaceOrderResult(PlaceOrderResult::FAILED, null, null, null, $this->translator);
        }

        if ($response->hasError()) {
            return $this->getPlaceOrderErrorResult($response->getError()['code']);
        }

        $result = $response->getResult();

        return new PlaceOrderResult(
            PlaceOrderResult::SUCCESS,
            $result['id'],
            $result['left'],
            $result['amount'],
            $this->translator
        );
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
            return new TradeResult(TradeResult::FAILED, $this->translator);
        }

        if ($response->hasError()) {
            return $this->getCancelOrderErrorResult($response->getError()['code']);
        }

        return new TradeResult(TradeResult::SUCCESS, $this->translator);
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

    public function getFinishedOrderDetails(int $orderId): array
    {
        $response = $this->jsonRpc->send(self::FINISHED_ORDER_METHOD_DETAILS, [
            $orderId,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function getPendingOrderDetails(string $marketName, int $orderId): array
    {
        $response = $this->jsonRpc->send(self::PENDING_ORDER_METHOD_DETAILS, [
            $marketName,
            $orderId,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function getOrderDetails(int $orderId, int $offset, int $limit): array
    {
        $response = $this->jsonRpc->send(self::ORDER_DETAILS_METHOD, [
            $orderId,
            $offset,
            $limit,
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
            ? new TradeResult($errorMapping[$errorCode], $this->translator)
            : new TradeResult(TradeResult::FAILED, $this->translator);
    }

    private function getExecuteOrderErrorResult(int $errorCode): TradeResult
    {
        $errorMapping = [
            self::INSUFFICIENT_BALANCE_CODE => TradeResult::INSUFFICIENT_BALANCE,
            self::NO_ENOUGH_TRADER_CODE => TradeResult::NO_ENOUGH_TRADER,
        ];

        return array_key_exists($errorCode, $errorMapping)
            ? new TradeResult($errorMapping[$errorCode], $this->translator)
            : new TradeResult(TradeResult::FAILED, $this->translator);
    }

    private function getPlaceOrderErrorResult(int $errorCode): PlaceOrderResult
    {
        $errorMapping = [
            self::INSUFFICIENT_BALANCE_CODE => PlaceOrderResult::INSUFFICIENT_BALANCE,
            self::SMALL_AMOUNT_CODE => PlaceOrderResult::SMALL_AMOUNT,
        ];

        return array_key_exists($errorCode, $errorMapping)
            ? new PlaceOrderResult($errorMapping[$errorCode], null, null, null, $this->translator)
            : new PlaceOrderResult(PlaceOrderResult::FAILED, null, null, null, $this->translator);
    }
}
