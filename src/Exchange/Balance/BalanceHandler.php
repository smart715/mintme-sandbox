<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\TraderBalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Manager\UserManager;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Psr\Log\LoggerInterface;

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

    /** @var TraderBalanceViewFactoryInterface */
    private $traderBalanceViewFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TokenNameConverterInterface $converter,
        BalanceFetcherInterface $balanceFetcher,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        TraderBalanceViewFactoryInterface $traderBalanceViewFactory,
        LoggerInterface $logger
    ) {
        $this->converter = $converter;
        $this->balanceFetcher = $balanceFetcher;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->traderBalanceViewFactory = $traderBalanceViewFactory;
        $this->logger = $logger;
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

    /** @inheritDoc */
    public function topTraders(
        TradebleInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 40
    ): array {
        $tradableName = $tradable instanceof Token
            ? $this->converter->convert($tradable)
            : $tradable->getSymbol();

        $result = $this->balanceFetcher->topBalances($tradableName, $extend);

        return $this->traderBalanceViewFactory->create($this, $result, $tradable, $limit, $extend, $incrementer, $max);
    }

    public function isNotExchanged(Token $token, int $amount): bool
    {
        $available = $this->balance($token->getProfile()->getUser(), $token)->getAvailable();
        $balance = $this->moneyWrapper->parse((string)$amount, $available->getCurrency()->getCode());

        return $available->equals($balance);
    }

    /**
     * @throws FetchException
     * @throws BalanceException
     */
    private function update(User $user, Token $token, Money $amount, string $type): void
    {
        try {
            $this->balanceFetcher->update(
                $user->getId(),
                $this->converter->convert($token),
                $this->moneyWrapper->format($amount),
                $type
            );
        } catch (BalanceException $exception) {
            $this->logger->error(
                "Failed to update '{$user->getEmail()}' balance for {$token->getSymbol()}.
                Requested: {$amount->getAmount()}. Type: {$type}. Reason: {$exception->getMessage()}"
            );

            throw $exception;
        }

        if (!in_array($token, $user->getTokens()) && $token->getId()) {
            $userToken = (new UserToken())->setToken($token)->setUser($user);
            $this->entityManager->persist($userToken);
            $user->addToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
