<?php

namespace App\Exchange\Balance;

use App\Communications\JsonRpcInterface;
use App\Entity\Token;
use App\Entity\User;
use App\Utils\RandomNumberInterface;
use App\Utils\TokenNameConverterInterface;

class BalanceHandler implements BalanceHandlerInterface
{
    private const UPDATE_BALANCE_METHOD = 'balance.update';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var TokenNameConverterInterface */
    private $converter;

    /** @var RandomNumberInterface */
    private $random;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        TokenNameConverterInterface $converter,
        RandomNumberInterface $randomNumber
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->converter = $converter;
        $this->random = $randomNumber;
    }

    public function deposit(User $user, Token $token, string $amount): void
    {
        $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
            $user->getId(),
            $this->converter->convert(
                $token
            ),
            'deposit',
            $this->random->getNumber(),
            $amount,
            [ 'extra' => 1 ],
        ]);
    }

    public function withdraw(User $user, Token $token, string $amount): void
    {
        $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
            $user->getId(),
            $this->converter->convert(
                $token
            ),
            'withdraw',
            $this->random->getNumber(),
            $amount,
            [ 'extra' => 1 ],
        ]);
    }
}
