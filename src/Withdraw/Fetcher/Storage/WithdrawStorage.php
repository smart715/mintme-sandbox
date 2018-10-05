<?php

namespace App\Withdraw\Fetcher\Storage;

use App\Communications\JsonRpcInterface;

class WithdrawStorage implements StorageAdapterInterface
{
    private const RPC_HISTORY = 'history';
    private const RPC_BALANCE = 'balance';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var string */
    private $service;

    public function __construct(JsonRpcInterface $jsonRpc, string $service)
    {
        $this->jsonRpc = $jsonRpc;
        $this->service = $service;
    }

    /** {@inheritdoc} */
    public function requestHistory(int $id): array
    {
        return $this->sendRequest(self::RPC_HISTORY, [
            'id' => $id,
            'service' => $this->service,
        ]);
    }

    /** {@inheritdoc} */
    public function requestBalance(string $symbol): array
    {
        return $this->sendRequest(self::RPC_BALANCE, [
            'crypto' => $symbol,
        ]);
    }

    private function sendRequest(string $method, array $params): array
    {
        try {
            $response = $this->jsonRpc->send($method, $params);
        } catch (\Throwable $exception) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $response->getResult();
    }
}
