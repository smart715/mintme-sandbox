<?php

namespace App\Exchange\Balance;

use App\Communications\JsonRpcInterface;
use App\Entity\Token;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\SummaryResult;
use App\Utils\RandomNumberInterface;
use App\Utils\TokenNameConverterInterface;

class BalanceHandler implements BalanceHandlerInterface
{
    private const UPDATE_BALANCE_METHOD = 'balance.update';
    private const SUMMARY_METHOD = 'asset.summary';

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

    /** {@inheritdoc} */
    public function deposit(User $user, Token $token, int $amount): void
    {
        $this->updateBalance($user, $token, $amount, 'deposit');
    }

    /** {@inheritdoc} */
    public function withdraw(User $user, Token $token, int $amount): void
    {
        $this->updateBalance($user, $token, $amount, 'withdraw');
    }

    public function summary(Token $token): SummaryResult
    {
        try {
            $response = $this->jsonRpc->send(self::SUMMARY_METHOD, [
                $this->converter->convert($token)
            ]);
        } catch (\Throwable $exception) {
            return SummaryResult::fail();
        }

        if ($response->hasError()) {
            return SummaryResult::fail();
        }

        $result = $response->getResult();

        return SummaryResult::success(
            $result['name'],
            (int)$result['total_balance'],
            (int)$result['available_balance'],
            $result['available_count'],
            (int)$result['freeze_balance'],
            $result['freeze_count']
        );
    }

    /**
     * @throws \Exception
     * @throws BalanceException
     */
    private function updateBalance(User $user, Token $token, int $amount, string $type): void
    {
        $responce = $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
            $user->getId(),
            $this->converter->convert(
                $token
            ),
            $type,
            $this->random->getNumber(),
            (string)$amount,
            [ 'extra' => 1 ],
        ]);

        if ($responce->hasError()) {
            throw new BalanceException();
        }
    }
}
