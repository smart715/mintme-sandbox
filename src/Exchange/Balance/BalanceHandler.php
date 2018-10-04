<?php

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\User;

class BalanceHandler implements BalanceHandlerInterface
{
    private const UPDATE_BALANCE_METHOD = 'balace.update';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function deposit(User $user, string $assetName, string $balance): void
    {
        $params = [
            $user->getId(),
            $assetName,
            'deposit',
            100,
            $balance,
        ];

        $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [$params]);
    }

    public function withdraw(User $user, string $assetName, string $balance): void
    {
        $params = [
            $user->getId(),
            $assetName,
            'withdraw',
            100,
            $balance,
        ];

        $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [$params]);
    }
}
