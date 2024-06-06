<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalancesArrayFactoryInterface;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\TraderBalanceViewFactoryInterface;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Exchange\Balance\Factory\UpdateBalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceHistory;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Manager\BonusBalanceTransactionManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Mercure\Publisher as MercurePublisher;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Utils\RandomNumber;
use App\Utils\Symbols;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use Psr\Log\LoggerInterface;

/**
 * Class BalanceHandler
 *
 * @package App\Exchange\Balance
 */
class BalanceHandler implements BalanceHandlerInterface
{
    private TokenNameConverterInterface $converter;
    private BalanceFetcherInterface $balanceFetcher;
    private UserManagerInterface $userManager;
    private BonusBalanceTransactionManagerInterface $bonusBalanceTransactionManager;
    private BalancesArrayFactoryInterface $balanceArrayFactory;
    private MoneyWrapperInterface $moneyWrapper;
    private TraderBalanceViewFactoryInterface $traderBalanceViewFactory;
    private LoggerInterface $logger;
    private UserTokenManagerInterface $userTokenManager;
    private UpdateBalanceViewFactoryInterface $updateBalanceViewFactory;
    private ?int $transactionsStartTime;
    private array $recordedTransactions;
    private array $recordedBonusTransactions;
    private RandomNumber $randomNumber;
    private MercurePublisher $mercurePublisher;

    public function __construct(
        TokenNameConverterInterface $converter,
        BalanceFetcherInterface $balanceFetcher,
        UserManagerInterface $userManager,
        BonusBalanceTransactionManagerInterface $bonusBalanceTransactionManager,
        BalancesArrayFactoryInterface $balanceArrayFactory,
        MoneyWrapperInterface $moneyWrapper,
        TraderBalanceViewFactoryInterface $traderBalanceViewFactory,
        LoggerInterface $logger,
        UserTokenManagerInterface $userTokenManager,
        UpdateBalanceViewFactoryInterface $updateBalanceViewFactory,
        RandomNumber $randomNumber,
        MercurePublisher $mercurePublisher
    ) {
        $this->converter = $converter;
        $this->balanceFetcher = $balanceFetcher;
        $this->bonusBalanceTransactionManager = $bonusBalanceTransactionManager;
        $this->userManager = $userManager;
        $this->balanceArrayFactory = $balanceArrayFactory;
        $this->moneyWrapper = $moneyWrapper;
        $this->traderBalanceViewFactory = $traderBalanceViewFactory;
        $this->logger = $logger;
        $this->userTokenManager = $userTokenManager;
        $this->updateBalanceViewFactory = $updateBalanceViewFactory;
        $this->transactionsStartTime = null;
        $this->recordedTransactions = [];
        $this->recordedBonusTransactions = [];
        $this->randomNumber = $randomNumber;
        $this->mercurePublisher = $mercurePublisher;
    }

    public function beginTransaction(): void
    {
        $this->transactionsStartTime = time();
        $this->recordedTransactions = [];
        $this->recordedBonusTransactions = [];
    }

    public function isTransactionStarted(): bool
    {
        return null !== $this->transactionsStartTime;
    }

    public function isServiceAvailable(): bool
    {
        try {
            $this->balanceFetcher->balance(1, [Symbols::BTC]);

            return true;
        } catch (\Throwable $err) {
            $this->logger->error('Viabtc seems unavailable', [
                'error' => $err->getMessage(),
            ]);

            return false;
        }
    }

    public function rollback(): void
    {
        if (!$this->isTransactionStarted()) {
            throw new \RuntimeException('Transaction is not started, use beginTransaction() before rollback()');
        }

        $until = time() + 1;

        try {
            $allTransactionsToRollback = $this->fetchAllTransactions($this->transactionsStartTime, $until);

            $this->revertTransactions($allTransactionsToRollback);
        } catch (\Throwable $e) {
            $this->logRollbackFailure($allTransactionsToRollback ?? null, $until, $e);
        } finally {
            $this->transactionsStartTime = null;
            $this->recordedTransactions = [];
        }

        try {
            $this->revertBonusUpdates($this->recordedBonusTransactions);
        } catch (\Throwable $e) {
            $this->logRollbackFailure($this->recordedBonusTransactions, $until, $e);
        }

        $this->recordedTransactions = [];
        $this->recordedBonusTransactions = [];
        $this->transactionsStartTime = null;
    }

    public function deposit(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        ?int $businessId = null
    ): void {
        try {
            $this->update($user, $tradable, $amount, 'deposit', $businessId);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function depositBonus(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $bonusType
    ): void {
        try {
            $this->bonusBalanceTransactionManager->updateBalance($user, $tradable, $amount, 'deposit', $bonusType);
            $this->addBonusRecordIfTransactionStarted($user, $tradable, $amount, 'deposit', $bonusType);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Failed to deposit bonus balance of user '{$user->getEmail()}' for {$tradable->getSymbol()}.
                Requested: {$amount->getAmount()}. Type: $bonusType. Reason: {$e->getMessage()}"
            );

            throw $e;
        }
    }

    public function withdraw(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        ?int $businessId = null
    ): UpdateBalanceView {
        try {
            $updateBalanceView = $this->update($user, $tradable, $amount->negative(), 'withdraw', $businessId);
        } catch (\Throwable $e) {
            throw $e;
        }

        $this->mercurePublisher->publishWithdrawEvent($user, $tradable);

        return $updateBalanceView;
    }

    public function withdrawBonus(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $bonusType
    ): UpdateBalanceView {
        try {
            $currentBonusBalance = $this->bonusBalanceTransactionManager->getBalance($user, $tradable)
                ?? new Money('0', $amount->getCurrency());

            if ($currentBonusBalance->lessThan($amount)) {
                $amountToWithdrawFromBalance = $amount->subtract($currentBonusBalance);
                $amountToWithdrawFromBonusBalance = $amount->subtract($amountToWithdrawFromBalance);

                $this->bonusBalanceTransactionManager->updateBalance(
                    $user,
                    $tradable,
                    $amountToWithdrawFromBonusBalance,
                    Type::WITHDRAW,
                    $bonusType
                );

                $this->addBonusRecordIfTransactionStarted(
                    $user,
                    $tradable,
                    $amountToWithdrawFromBonusBalance,
                    Type::WITHDRAW,
                    $bonusType
                );
                $this->update($user, $tradable, $amountToWithdrawFromBalance->negative(), $bonusType);

                return new UpdateBalanceView($amountToWithdrawFromBalance, $amountToWithdrawFromBonusBalance);
            } else {
                $this->bonusBalanceTransactionManager->updateBalance(
                    $user,
                    $tradable,
                    $amount,
                    Type::WITHDRAW,
                    $bonusType
                );

                $this->addBonusRecordIfTransactionStarted(
                    $user,
                    $tradable,
                    $amount,
                    Type::WITHDRAW,
                    $bonusType
                );

                return new UpdateBalanceView(new Money('0', $amount->getCurrency()), $amount);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                "Failed to deposit bonus balance of user '{$user->getEmail()}' for {$tradable->getSymbol()}.
                Requested: {$amount->getAmount()}. Type: $bonusType. Reason: {$e->getMessage()}"
            );

            throw $e;
        }
    }

    public function history(
        int $userId,
        string $tokenName,
        ?string $type,
        int $startTime = 0,
        int $endTime = 0,
        int $offset = 0,
        int $limit = 50
    ): BalanceHistory {
        try {
             return $this->balanceFetcher->history(
                 $userId,
                 $tokenName,
                 $type,
                 $startTime,
                 $endTime,
                 $offset,
                 $limit
             );
        } catch (\Throwable $e) {
            $this->logger->error(
                "Failed to get balance history of user 
                '{$this->userManager->find($userId)->getEmail()}' for $tokenName.
                Type: $type. Reason: {$e->getMessage()}"
            );

            throw $e;
        }
    }

    public function summary(Token $token): SummaryResult
    {
        return $this->balanceFetcher->summary($this->converter->convert($token));
    }

    public function balances(User $user, array $tradables): BalanceResultContainer
    {
        $balances = $this->balanceFetcher
            ->balance($user->getId(), array_map(function (TradableInterface $tradable) {
                return $this->converter->convert($tradable);
            }, $tradables));

        $bonusBalances = $this->bonusBalanceTransactionManager->getBalances($user);

        foreach ($bonusBalances as $bonusBalance) {
            $tokenIdConverted = $this->converter->convert($bonusBalance->getTradable());

            if (array_key_exists($tokenIdConverted, $balances->getAll())) {
                $balances->getAll()[$tokenIdConverted]->setBonus($bonusBalance->getAmount());
            }
        }

        return $balances;
    }

    public function getReferralBalances(User $user, array $cryptosValues): array
    {
        $allBalances = [];
        $balances = $this->balances(
            $user,
            $cryptosValues
        );

        foreach ($cryptosValues as $cryptoValue) {
            $converted = $this->converter->convert($cryptoValue);
            $allBalances[$cryptoValue->getSymbol()] = $balances->get($converted)->getReferral();
        }

        return $allBalances;
    }

    public function indexedBalances(User $user, array $tokens): array
    {
        $indexedBalances = [];
        $balances = $this->balances(
            $user,
            $tokens,
        );

        foreach ($tokens as $token) {
            $indexedBalances[$token->getSymbol()] = $balances->get($this->converter->convert($token));
        }

        return $indexedBalances;
    }

    public function balance(User $user, TradableInterface $tradable): BalanceResult
    {
        return $this->balances($user, [$tradable])
            ->get($this->converter->convert($tradable));
    }

    public function exchangeBalance(User $user, Token $token, bool $withBonus = false): Money
    {
        $balance = $this->balance($user, $token);
        $exchangeBalance = $balance->getAvailable();

        if ($token->getLockIn() && $token->getOwnerId() === $user->getId()) {
            return $token->isDeployed()
                ? $exchangeBalance = $exchangeBalance->subtract($token->getLockIn()->getFrozenAmountWithReceived())
                : $exchangeBalance = $exchangeBalance->subtract($token->getLockIn()->getFrozenAmount());
        }

        if ($withBonus) {
            $exchangeBalance = $exchangeBalance->add($balance->getBonus());
        }

        return $exchangeBalance;
    }

    public function topHolders(
        TradableInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 30
    ): array {
        $tradableName = $tradable instanceof Token
            ? $this->converter->convert($tradable)
            : $tradable->getSymbol();

        $balances = $this->balanceFetcher->topBalances($tradableName, $extend, 0, true);

        // createBalanceViewWithExtension expects [1] to be the available balance, but [1] is total, [2] is available
        foreach ($balances as $balance) {
            $balance[1] = $balance[2];
        }

        $filteredBalances = $this->filterLowBalances($balances);

        return $this->createBalanceViewWithExtension(
            $filteredBalances,
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

    public function update(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $type,
        ?int $businessId = null
    ): UpdateBalanceView {
        try {
            $businessId = $businessId ?? $this->randomNumber->getNumber();

            $updateBalanceResult = $this->balanceFetcher->update(
                $user->getId(),
                $tokenName = $this->converter->convert($tradable),
                $this->moneyWrapper->format($amount),
                $type,
                $businessId
            );

            $this->addRecordIfTransactionStarted(
                $user->getId(),
                $tokenName,
                $this->moneyWrapper->format($amount),
                $type,
                $businessId,
            );

            $updateBalanceView = $this->updateBalanceViewFactory->createUpdateBalanceView(
                $updateBalanceResult,
                $tradable->getMoneySymbol()
            );

            if ($amount->isNegative() && $updateBalanceView->getChange()->isZero()) {
                throw new BalanceException('failed to withdraw. Withdrawn 0');
            }
        } catch (BalanceException $e) {
            $this->logger->error(
                "Failed to update '{$user->getEmail()}' balance for {$tradable->getSymbol()}.
                Requested: {$amount->getAmount()}. Type: $type. Reason: {$e->getMessage()}"
            );

            throw $e;
        }

        if ($tradable instanceof Token) {
            $this->updateUserTokenRelation($user, $tradable);
        }

        return $this->updateBalanceViewFactory->createUpdateBalanceView(
            $updateBalanceResult,
            $tradable->getMoneySymbol()
        );
    }

    public function updateUserTokenRelation(User $user, TradableInterface $tradable, bool $isReferral = false): void
    {
        $balance = $this->balance($user, $tradable)->getAvailable();

        if ($tradable instanceof Token) {
            $this->userTokenManager->updateRelation($user, $tradable, $balance, $isReferral);
        }
    }

    /**
     * @param array[] $balances
     * @param TradableInterface $tradable
     * @param int $limit
     * @param int $extend
     * @param int $incrementer
     * @param int $max
     * @return TraderBalanceView[]
     */
    private function createBalanceViewWithExtension(
        array $balances,
        TradableInterface $tradable,
        int $limit,
        int $extend,
        int $incrementer,
        int $max
    ): array {
        if (0 === count($balances)) {
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
     * @param TradableInterface $tradable
     * @param array $userIds
     * @return array
     */
    private function getUserTradables(TradableInterface $tradable, array $userIds): array
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
    private function filterLowBalances(array $balances): array
    {
        return array_filter($balances, static function ($key) {
            return round($key[1]) > 0;
        });
    }

    private function addRecordIfTransactionStarted(
        int $userId,
        string $tokenName,
        string $amount,
        string $type,
        ?int $businessId = null
    ): void {
        if (null === $this->transactionsStartTime) {
            return;
        }

        $this->recordedTransactions[] = [
            'user_id' => $userId,
            'token_name' => $tokenName,
            'amount' => $amount,
            'type' => $type,
            'business_id' => $businessId,
        ];
    }

    private function addBonusRecordIfTransactionStarted(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $type,
        string $bonusType
    ): void {
        if (null === $this->transactionsStartTime) {
            return;
        }

        $this->recordedBonusTransactions[] = [
            'user' => $user,
            'token' => $tradable,
            'amount' => $amount,
            'type' => $type,
            'bonus_type' => $bonusType,
        ];
    }

    /**
     * @throws \Throwable
     */
    private function revertTransactions(array $allTransactionsToRollback): void
    {
        try {
            foreach ($allTransactionsToRollback as $transaction) {
                $this->revertBalanceUpdate($transaction);
            }
        } catch (\Throwable $e) {
            $this->logRevertTransactionFailure($allTransactionsToRollback, $e);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws \App\Communications\Exception\FetchException
     * @throws BalanceException
     */
    private function revertBalanceUpdate(array $transaction): void
    {
        try {
            $change = (string)($transaction['amount'] * -1);

            $this->balanceFetcher->update(
                $transaction['user_id'],
                $transaction['token_name'],
                $change,
                $transaction['type'],
            );
        } catch (\Throwable $e) {
            $this->logger->error('failed to revert balance update', [
                'exception' => $e,
                'transaction' => $transaction,
            ]);

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    private function fetchAllTransactions(int $from, int $until): array
    {
        $allTransactionsToRollback = [];

        foreach ($this->recordedTransactions as $record) {
            try {
                $history = $this->history(
                    $record['user_id'],
                    $record['token_name'],
                    $record['type'],
                    $from,
                    $until,
                    0,
                    50
                );
                $isRecordProcessed = in_array(
                    $record['business_id'],
                    array_column(array_column($history->getRecords(), 'detail'), 'id')
                );
            } catch (\Throwable $e) {
                $this->logFetchHistoryFailure($record, $until, $e);

                throw $e;
            }

            if ($isRecordProcessed) {
                $allTransactionsToRollback[] = $record;
            }
        }

        return $allTransactionsToRollback;
    }

    private function revertBonusUpdates(array $bonusTransactions): void
    {
        foreach ($bonusTransactions as $bonusTransaction) {
            $bonusTransaction['amount'] = $bonusTransaction['amount']->negative();
            $transaction = array_values($bonusTransaction);
            $this->bonusBalanceTransactionManager->updateBalance(...$transaction);
        }
    }

    private function logFetchHistoryFailure(array $record, int $until, \Throwable $e): void
    {
        $this->logger->error(
            "Failed to get history for user {$record['user_id']}, token {$record['token_name']}, type
            {$record['type']}, from $this->transactionsStartTime to $until, offset 0 and limit 50",
            ['exception' => $e]
        );
    }

    private function logRevertTransactionFailure(array $allTransactionsToRollback, \Throwable $e): void
    {
        $this->logger->error('Failed to revert Failed transactions', [
            'transactions' => $allTransactionsToRollback,
            'exception' => $e,
        ]);
    }

    private function logRollbackFailure(?array $allTransactionsToRollback, int $until, \Throwable $e): void
    {
        $this->logger->error('Failed to rollback transactions', [
            'transactions to rollback' => $allTransactionsToRollback,
            'transaction start time' => $this->transactionsStartTime,
            'transaction end time' => $until,
            'rollback start time' => $until - 1,
            'rollback end time' => time(),
            'exception' => $e,
        ]);
    }
}
