<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalancesArrayFactoryInterface;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\TraderBalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Manager\UserManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Psr\Log\LoggerInterface;

/**
 * Class BalanceHandler
 *
 * @package App\Exchange\Balance
 */
class BalanceHandler implements BalanceHandlerInterface
{
    /** @var TokenNameConverterInterface */
    private TokenNameConverterInterface $converter;

    /** @var BalanceFetcherInterface */
    private BalanceFetcherInterface $balanceFetcher;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var UserManagerInterface */
    private UserManagerInterface $userManager;

    /** @var BalancesArrayFactoryInterface */
    private BalancesArrayFactoryInterface $balanceArrayFactory;

    /** @var MoneyWrapperInterface */
    private MoneyWrapperInterface $moneyWrapper;

    /** @var TraderBalanceViewFactoryInterface */
    private TraderBalanceViewFactoryInterface $traderBalanceViewFactory;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * BalanceHandler constructor.
     *
     * @param TokenNameConverterInterface $converter
     * @param BalanceFetcherInterface $balanceFetcher
     * @param EntityManagerInterface $entityManager
     * @param UserManagerInterface $userManager
     * @param BalancesArrayFactoryInterface $balanceArrayFactory
     * @param MoneyWrapperInterface $moneyWrapper
     * @param TraderBalanceViewFactoryInterface $traderBalanceViewFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        TokenNameConverterInterface $converter,
        BalanceFetcherInterface $balanceFetcher,
        EntityManagerInterface $entityManager,
        UserManagerInterface $userManager,
        BalancesArrayFactoryInterface $balanceArrayFactory,
        MoneyWrapperInterface $moneyWrapper,
        TraderBalanceViewFactoryInterface $traderBalanceViewFactory,
        LoggerInterface $logger
    ) {
        $this->converter = $converter;
        $this->balanceFetcher = $balanceFetcher;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->balanceArrayFactory = $balanceArrayFactory;
        $this->moneyWrapper = $moneyWrapper;
        $this->traderBalanceViewFactory = $traderBalanceViewFactory;
        $this->logger = $logger;
    }

    public function deposit(User $user, Token $token, Money $amount, ?int $businessId = null): void
    {
        try {
            $this->update($user, $token, $amount, 'deposit', $businessId);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function withdraw(User $user, TradebleInterface $tradable, Money $amount, ?int $businessId = null): void
    {
        try {
            $this->update($user, $tradable, $amount->negative(), 'withdraw', $businessId);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function summary(Token $token): SummaryResult
    {
        return $this->balanceFetcher->summary($this->converter->convert($token));
    }

    public function balances(User $user, array $tradables): BalanceResultContainer
    {
        return $this->balanceFetcher
            ->balance($user->getId(), array_map(function (TradebleInterface $tradable) {
                return $this->converter->convert($tradable);
            }, $tradables));
    }

    public function balance(User $user, TradebleInterface $tradable): BalanceResult
    {
        return $this->balances($user, [$tradable])
            ->get($this->converter->convert($tradable));
    }

    public function exchangeBalance(User $user, Token $token): Money
    {
        $balance = $this->balance($user, $token)->getAvailable();

        if ($token->getLockIn()) {
            return $token->isDeployed()
                ? $balance = $balance->subtract($token->getLockIn()->getFrozenAmountWithReceived())
                : $balance = $balance->subtract($token->getLockIn()->getFrozenAmount());
        }

        return $balance;
    }

    public function topHolders(
        TradebleInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 30
    ): array {
        $tradableName = $tradable instanceof Token
            ? $this->converter->convert($tradable)
            : $tradable->getSymbol();

        $balances = $this->balanceFetcher->topBalances($tradableName, $extend);
        $normalizeBalances = $this->normalizeBalances($balances);

        return $this->createBalanceViewWithExtension(
            $normalizeBalances,
            $tradable,
            $limit,
            $extend,
            $incrementer,
            $max
        );
    }

    public function isNotExchanged(Token $token, int $amount): bool
    {
        $available = $this->balance($token->getProfile()->getUser(), $token)->getAvailable();
        $balance = $this->moneyWrapper->parse((string)$amount, $available->getCurrency()->getCode());

        return $available->equals($balance);
    }

    public function update(User $user, TradebleInterface $tradable, Money $amount, string $type, ?int $businessId = null): void
    {
        try {
            $this->balanceFetcher->update(
                $user->getId(),
                $this->converter->convert($tradable),
                $this->moneyWrapper->format($amount),
                $type,
                $businessId
            );
        } catch (BalanceException $e) {
            $this->logger->error(
                "Failed to update '{$user->getEmail()}' balance for {$tradable->getSymbol()}.
                Requested: {$amount->getAmount()}. Type: {$type}. Reason: {$e->getMessage()}"
            );

            throw $e;
        } catch (\Throwable $e) {
            throw $e;
        }

        if ($tradable instanceof Token) {
            $this->updateUserTokenRelation($user, $tradable);
        }
    }

    public function updateUserTokenRelation(User $user, Token $token): void
    {
        if ($token->getId()) {
            $tokenExist = array_filter($user->getTokens(), static function (Token $userToken) use ($token) {
                return $userToken->getId() === $token->getId();
            });

            if (!$tokenExist) {
                $userToken = (new UserToken())->setToken($token)->setUser($user);
                $this->entityManager->persist($userToken);
                $user->addToken($userToken);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param array[] $balances
     * @param TradebleInterface $tradable
     * @param int $limit
     * @param int $extend
     * @param int $incrementer
     * @param int $max
     * @return TraderBalanceView[]
     */
    private function createBalanceViewWithExtension(
        array $balances,
        TradebleInterface $tradable,
        int $limit,
        int $extend,
        int $incrementer,
        int $max
    ): array {
        if (0 === count($balances) || ($tradable instanceof Token && null === $tradable->getId())) {
            return [];
        }

        $isMax = $max <= $extend || count($balances) < $extend;
        $balances = $this->balanceArrayFactory->create($balances);

        $usersTradables = count($balances) > 0 ? $this->getUserTradables($tradable, array_keys($balances)) : [];

        if ($isMax || count($usersTradables) >= $limit) {
            return $this->traderBalanceViewFactory->create($usersTradables, $balances, $limit);
        }

        return $this->topHolders($tradable, $limit, $extend + $incrementer, $incrementer, $max);
    }

    /**
     * @param TradebleInterface $tradable
     * @param array $userIds
     * @return array
     */
    private function getUserTradables(TradebleInterface $tradable, array $userIds): array
    {
        if ($tradable instanceof Token) {
            return $this->userManager->getUserToken($tradable, $userIds);
        }

        if ($tradable instanceof Crypto) {
            return $this->userManager->getUserCrypto($tradable, $userIds);
        }

        return [];
    }

    /**
     * @param array $balances
     * @return array
     */
    private function normalizeBalances(array $balances): array
    {
        return array_filter($balances, static function ($key) {
            return round($key[1]) > 0;
        });
    }
}
