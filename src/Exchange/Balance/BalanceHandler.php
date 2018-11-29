<?php

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Utils\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class BalanceHandler implements BalanceHandlerInterface
{
    /** @var TokenNameConverterInterface */
    private $converter;

    /** @var BalanceFetcherInterface */
    private $balanceFetcher;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        TokenNameConverterInterface $converter,
        BalanceFetcherInterface $balanceFetcher,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->converter = $converter;
        $this->balanceFetcher = $balanceFetcher;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function deposit(User $user, Token $token, Money $amount): void
    {
        $this->update($user, $token, $amount, 'deposit');
    }

    /** {@inheritdoc} */
    public function withdraw(User $user, Token $token, Money $amount): void
    {
        $this->update($user, $token, $amount->negative(), 'withdraw');
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

    /**
     * @throws FetchException
     * @throws BalanceException
     */
    private function update(User $user, Token $token, Money $amount, string $type): void
    {
        $this->balanceFetcher->update(
            $user->getId(),
            $this->converter->convert($token),
            $this->moneyWrapper->format($amount),
            $type
        );

        if (!in_array($token, $user->getRelatedTokens()) && $token->getId()) {
            $user->addRelatedToken($token);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
