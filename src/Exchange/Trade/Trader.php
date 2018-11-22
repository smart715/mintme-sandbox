<?php

namespace App\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\OrderFilterConfig;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class Trader implements TraderInterface
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

    /** @var LimitOrderConfig */
    private $config;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        LimitOrderConfig $config,
        EntityManagerInterface $entityManager
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function placeOrder(Order $order): TradeResult
    {

        try {
            $response = $this->jsonRpc->send(self::PLACE_ORDER_METHOD, [
                $order->getMakerId(),
                $order->getMarket()->getHiddenName(),
                $order->getSide(),
                $order->getAmount(),
                $order->getPrice(),
                (string) $this->config->getTakerFeeRate(),
                (string) $this->config->getMakerFeeRate(),
                '',
                0,
                "0",
            ]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return self::INSUFFICIENT_BALANCE_CODE === $response->getError()['code']
                ? new TradeResult(TradeResult::INSUFFICIENT_BALANCE)
                : new TradeResult(TradeResult::FAILED);
        }

        $maker = $this->getUserRepository()->find($order->getMakerId());
        $taker = $this->getUserRepository()->find($order->getTakerId() ?? 0);

        $token = $order->getMarket()->getToken();

        if (null !== $token) {
            $this->updateUsers([$maker, $taker], $token);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    public function cancelOrder(Order $order): TradeResult
    {
        try {
            $response = $this->jsonRpc->send(self::CANCEL_ORDER_METHOD, [
                $order->getMakerId(),
                $order->getMarket()->getHiddenName(),
                $order->getId(),
            ]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return $this->getCancelOrderErrorResult($response->getError()['code']);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    /**
     * @inheritdoc
     */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        try {
            $response = $this->jsonRpc->send(self::FINISHED_ORDERS_METHOD, [
                $user->getId(),
                $market->getHiddenName(),
                $options['start_time'],
                $options['end_time'],
                $options['offset'],
                $options['limit'],
                Order::SIDE_MAP[$options['side']],
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::FINISHED_STATUS);
        }, $response->getResult()['records']);
    }

    /**
     * @inheritdoc
     */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        $params = [
            $user->getId(),
            $market->getHiddenName(),
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']],
        ];

        try {
            $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, $params);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::PENDING_STATUS);
        }, $response->getResult()['records']);
    }

    /**
     * @param User[] $users
     */
    private function updateUsers(array $users, Token $token): void
    {
        foreach ($users as $user) {
            if (null !== $user && !in_array($token, $user->getRelatedTokens())) {
                $user->addRelatedToken($token);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }
    }

    private function getUserRepository(): UserRepository
    {
        return $this->entityManager->getRepository(User::class);
    }

    private function createOrder(array $orderData, User $user, Market $market, string $status): Order
    {
        return new Order(
            $orderData['id'],
            $user->getId(),
            null,
            $market,
            $orderData['amount'],
            $orderData['side'],
            $orderData['price'],
            $status,
            $orderData['mtime']
        );
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
