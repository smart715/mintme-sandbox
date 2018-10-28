<?php

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Utils\TokenNameConverterInterface;
use Doctrine\ORM\EntityManagerInterface;

class BalanceHandler implements BalanceHandlerInterface
{
    /** @var TokenNameConverterInterface */
    private $converter;

    /** @var BalanceFetcherInterface */
    private $balanceFetcher;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        TokenNameConverterInterface $converter,
        BalanceFetcherInterface $balanceFetcher,
        EntityManagerInterface $entityManager
    ) {
        $this->converter = $converter;
        $this->balanceFetcher = $balanceFetcher;
        $this->entityManager = $entityManager;
    }

    /** {@inheritdoc} */
    public function deposit(User $user, Token $token, int $amount): void
    {
        $this->update($user, $token, $amount, 'deposit');
    }

    /** {@inheritdoc} */
    public function withdraw(User $user, Token $token, int $amount): void
    {
        $this->update($user, $token, $amount, 'withdraw');
    }

    public function summary(Token $token): SummaryResult
    {
        return $this->balanceFetcher->summary($this->converter->convert($token));
    }

    /**
     * @param Token[] $tokens
     */
    public function balances(User $user, array $tokens): BalanceResultContainer
    {
        return $this->balanceFetcher
            ->balance($user->getId(), array_map(function (Token $token) {
                return $this->converter->convert($token);
            }, $tokens));
    }

    public function balance(User $user, Token $token): BalanceResult
    {
        return $this->balances($user, [$token])
            ->get($this->converter->convert($token));
    }

    public function balanceWeb(User $user): BalanceResult
    {
        try {
            $response = $this->jsonRpc->send(self::BALANCE_METHOD, [
                $user->getId(),
                "WEB",
            ]);
        } catch (\Throwable $exception) {
            return BalanceResult::fail();
        }

        $result = $response->getResult();

        return BalanceResult::success(
            (float)$result['WEB']['available'],
            (float)$result['WEB']['freeze']
        );
    }

    /**
     * @throws FetchException
     * @throws \Exception
     */
    private function update(User $user, Token $token, int $amount, string $type): void
    {
        $this->balanceFetcher->update($user->getId(), $this->converter->convert($token), $amount, $type);

        if (!in_array($token, $user->getRelatedTokens())) {
            $user->addRelatedToken($token);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
