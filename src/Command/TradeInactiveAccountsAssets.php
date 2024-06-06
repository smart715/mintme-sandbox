<?php declare(strict_types = 1);

namespace App\Command;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\AbstractOrder;
use App\Exchange\Balance\BalanceFetcherInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Trade\TradeResult;
use App\Manager\CryptoManagerInterface;
use App\Manager\InactiveOrderManager;
use App\Manager\InactiveOrderManagerInterface;
use App\Manager\TokenCryptoManager;
use App\Manager\TokenPromotionManager;
use App\Manager\UserManagerInterface;
use App\Repository\CryptoInternalTransactionRepository;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\Symbols;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/* Cron job added to DB. */
class TradeInactiveAccountsAssets extends Command
{
    /** @var string */
    protected static $defaultName = 'app:trade-inactive-accounts-assets';
    private const USER_BATCH_LIMIT = 1000;
    private const LOG_HEADER = "[trade-inactive-accounts-assets]";
    private const ACTIVITY_TYPES = [
        'trade',
        BalanceTransactionBonusType::DEPLOY_TOKEN,
        TokenCryptoManager::OPEN_MARKET_ID,
        'donation',
        TokenPromotionManager::BUY_PROMOTION_ID,
    ];

    private array $cryptoSymbols;
    /** @var Crypto[] */
    private array $cryptos;
    private BalanceFetcherInterface $balanceFetcher;
    private MoneyWrapperInterface $moneyWrapper;
    private QuickTradeConfig $quickTradeConfig;
    private UserManagerInterface $userManager;
    private ExchangerInterface $exchanger;
    private LoggerInterface $logger;
    private InactiveOrderManagerInterface $inactiveOrderManager;
    private EntityManagerInterface $entityManager;
    private ContractHandlerInterface $contractHandler;
    private CryptoManagerInterface $cryptoManager;
    private CryptoInternalTransactionRepository $cryptoInternalTransactionRepository;
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private string $period;
    private float $tradePercentage;
    private float $tradeUsdLimit;
    private float $minOrderUsd;
    private array $mintMeCoinMarkets;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        BalanceFetcherInterface $balanceFetcher,
        MoneyWrapperInterface $moneyWrapper,
        QuickTradeConfig $quickTradeConfig,
        UserManagerInterface $userManager,
        ExchangerInterface $exchanger,
        MarketFactoryInterface $marketFactory,
        InactiveOrderManagerInterface $inactiveOrderManager,
        EntityManagerInterface $entityManager,
        ContractHandlerInterface $contractHandler,
        CryptoInternalTransactionRepository $cryptoInternalTransactionRepository,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        LoggerInterface $logger,
        string $period,
        float $tradePercentage,
        float $tradeUsdLimit,
        float $minOrderUsd
    ) {
        $this->period = $period;
        $this->tradePercentage = $tradePercentage;
        $this->tradeUsdLimit = $tradeUsdLimit;
        $this->minOrderUsd = $minOrderUsd;
        $this->balanceFetcher = $balanceFetcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->quickTradeConfig = $quickTradeConfig;
        $this->userManager = $userManager;
        $this->inactiveOrderManager = $inactiveOrderManager;
        $this->entityManager = $entityManager;
        $this->contractHandler = $contractHandler;
        $this->cryptoManager = $cryptoManager;
        $this->cryptoInternalTransactionRepository = $cryptoInternalTransactionRepository;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->logger = $logger;
        $this->exchanger = $exchanger;

        $this->cryptos = array_filter(
            $this->cryptoManager->findAll(),
            fn (Crypto $crypto) => Symbols::WEB !== $crypto->getSymbol()
        );
        $this->cryptoSymbols =  array_map(fn (Crypto $crypto) => $crypto->getSymbol(), $this->cryptos);

        $this->mintMeCoinMarkets = $marketFactory->getMintMeCoinMarkets();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Trade inactive accounts assets')
            ->setHelp('This command trades a percentage of the assets of inactive accounts into MINTME');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Trading inactive accounts assets');
        $this->logger->info(self::LOG_HEADER . ' Trading inactive accounts assets');

        try {
            foreach ($this->cryptoSymbols as $crypto) {
                $output->writeln(self::LOG_HEADER. " Executing {$this->getName()} for $crypto");

                [$newOrders, $failedOrders] = $this->executePerCrypto($crypto);

                $output->writeln(self::LOG_HEADER. " Successful orders: $newOrders, failed orders: $failedOrders");
                $this->logger->info(self::LOG_HEADER . " Created orders: $newOrders, failed orders: $failedOrders");
            }
        } catch (\Throwable $e) {
            $output->writeln([self::LOG_HEADER. $e->getMessage()]);
            $this->logger->error(self::LOG_HEADER . " {$e->getMessage()}", $e->getTrace());

            return 1;
        }

        $output->writeln('Done');
        $this->logger->info(self::LOG_HEADER . ' Done');

        return 0;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function executePerCrypto(string $crypto): array
    {
        $market = $this->getMintMeMarket($crypto);

        if (!$market) {
            $this->logger->error(self::LOG_HEADER . " Market for $crypto not found");

            return [0, 0];
        }

        $topHolderData = array_filter(
            $this->fetchEligibleUsersBalance($crypto),
            fn(array $userBalance) => $this->inactiveForPeriod($userBalance[0]) &&
                !$this->userDidCryptoDeposit($userBalance[0])
        );

        [$successful, $failed] = [0, 0];

        foreach ($topHolderData as [$id, $balance, $availableBalance]) {
            $executePerUser = $this->executePerUser($id, $balance, $availableBalance, $market);

            if (0 === $executePerUser) {
                $successful++;
            } elseif (1 === $executePerUser) {
                $failed++;
            }
        }

        return [$successful, $failed];
    }

    private function fetchEligibleUsersBalance(string $crypto): array
    {
        [$looping, $userArrays, $limit, $offset] = [true, [], self::USER_BATCH_LIMIT, 0];

        while ($looping) {
            $eligibleUsers = array_filter(
                $this->balanceFetcher->topBalances($crypto, $limit, $offset, true),
                fn(array $balance) => $this->moreThanMinimumBalance($balance[2], $crypto)
            );

            $userArrays[] = $eligibleUsers;

            $lowestBalance = end($eligibleUsers);

            if (!$lowestBalance || !$this->moreThanMinimumBalance($lowestBalance[1], $crypto)) {
                $looping = false;
            } else {
                $offset += $limit;
            }
        }

        return array_merge(...$userArrays);
    }

    private function getMintMeMarket(string $crypto): ?Market
    {
        $market = null;

        foreach ($this->mintMeCoinMarkets as $mintMeCoinMarket) {
            if ($mintMeCoinMarket->getBase()->getSymbol() === $crypto) {
                $market = $mintMeCoinMarket;

                break;
            }
        }

        return $market;
    }

    private function moreThanMinimumBalance(string $amount, string $crypto): bool
    {
        $greaterThanOrderMin = (float)$this->calculateUsdValue($amount, $crypto) > $this->minOrderUsd;
        $greaterThanQuickTradeMinimum = $this->quickTradeConfig->getMinAmountBySymbol($crypto)
            ->lessThan($this->moneyWrapper->parse($amount, $crypto));

        return $greaterThanOrderMin && $greaterThanQuickTradeMinimum;
    }

    private function calculateUsdValue(string $amount, string $cryptoSymbol): string
    {
        $rates = $this->cryptoRatesFetcher->fetch();
        $price = $rates[$cryptoSymbol][Symbols::USD];

        return $this->moneyWrapper->format($this->moneyWrapper->parse((string)($amount * $price), Symbols::USD));
    }

    private function calculateCryptoValue(float $usdAmount, string $cryptoSymbol): string
    {
        $rates = $this->cryptoRatesFetcher->fetch();
        $price = $rates[$cryptoSymbol][Symbols::USD];

        return $this->moneyWrapper->format($this->moneyWrapper->parse((string)($usdAmount / $price), $cryptoSymbol));
    }

    private function inactiveForPeriod(
        int $userId
    ): bool {
        $from = new \DateTimeImmutable("-$this->period");
        $to = new \DateTimeImmutable();

        foreach (self::ACTIVITY_TYPES as $type) {
            if (!$this->inactiveForPeriodAndType($userId, $type, $from, $to)) {
                return false;
            }
        }

        return true;
    }

    private function userDidCryptoDeposit(int $userId): bool
    {
        $from = new \DateTimeImmutable("-$this->period");

        $user = $this->userManager->find($userId);

        if (!$user) {
            return false;
        }

        return $this->userDidExternalDeposit($user, $from) ||
            $this->userDidInternalDeposit($user, $from);
    }

    private function userDidExternalDeposit(User $user, \DateTimeImmutable $from): bool
    {
        $offset = 0;
        $limit = 100;

        while ($transactions = $this->contractHandler->getAllRawTransactions($user, $offset, $limit)) {
            foreach ($transactions as $transaction) {
                if ($from->getTimestamp() > $transaction['timestamp']) {
                    break;
                }

                if ('deposit' === $transaction['type'] &&
                    'paid' === $transaction['status'] &&
                    in_array($transaction['crypto'], $this->cryptoSymbols, true)
                ) {
                    return true;
                }
            }

            if (count($transactions) < $limit) {
                break;
            }

            $offset += $limit;
        }

        return false;
    }

    private function userDidInternalDeposit(User $user, \DateTimeImmutable $from): bool
    {
        $result = $this
            ->cryptoInternalTransactionRepository
            ->createQueryBuilder('it')
            ->where('it.user = :user')
            ->andWhere('it.crypto IN (:cryptos)')
            ->andWhere('it.type = :type')
            ->andWhere('it.date > :from')
            ->setParameter('user', $user)
            ->setParameter('cryptos', $this->cryptos)
            ->setParameter('type', Type::DEPOSIT)
            ->setParameter('from', $from)
            ->getQuery()
            ->getArrayResult();

        return count($result) > 0;
    }

    /*
     * @description Checks if the user has made any activity of the given type in the given period
     *
     * change viabtc *get_user_balance_history to handle multiple types/assets at once if it becomes a bottleneck
     */
    private function inactiveForPeriodAndType(
        int $userId,
        string $type,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): bool {
        foreach ([...$this->cryptoSymbols, Symbols::WEB] as $crypto) {
            try {
                $history = $this->balanceFetcher->history(
                    $userId,
                    $crypto,
                    $type,
                    $from->getTimestamp(),
                    $to->getTimestamp(),
                    0,
                    1,
                )->getRecords();

                if ($history && !$this->isOrderMadeByCommand($type, $history, $userId)) {
                    return false;
                }
            } catch (\Throwable $e) {
                $this->logger->error(self::LOG_HEADER . " Error fetching history for user $userId: {$e->getMessage()}");

                return false;
            }
        }

        return true;
    }

    private function isOrderMadeByCommand(string $type, array $history, int $userId): bool
    {
        return 'trade' === $type &&
            0 !== $history[0]['detail']['i'] && // donation
            $this->inactiveOrderManager->exists($userId, $history[0]['detail']['i']);
    }

    private function executePerUser(int $id, string $balance, string $availableBalance, Market $market): int
    {
        $marketName = $market->getQuote()->getSymbol() . $market->getBase()->getSymbol();
        $user = $this->userManager->find($id);

        if (!$user instanceof User) {
            $this->logger->error(self::LOG_HEADER . " User id $id not found");

            return 2;
        }

        if ($user->getProfile()->getCreated() > new \DateTimeImmutable("-$this->period")) {
            return 2;
        }

        $amount = $this->calculateAmount($balance, $market, $availableBalance);

        $tradeResult = $this->exchanger->executeOrder(
            $user,
            $market,
            $amount,
            AbstractOrder::BUY_SIDE,
            $this->quickTradeConfig->getSellCryptoFee(),
            false
        );

        if (TradeResult::SUCCESS !== $tradeResult->getResult()) {
            $this->logger->warning(self::LOG_HEADER.
                " Error executing order for user $id:{$tradeResult->getMessage()},".
                " Amount: $amount in market $marketName, order id: {$tradeResult->getId()}".
                ", available balance: $availableBalance, balance: $balance");

            return 1;
        }

        $order = InactiveOrderManager::make($user, $market, $tradeResult->getId());
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info(
            self::LOG_HEADER . "Order executed for user $id: {$tradeResult->getMessage()}, id {$tradeResult->getId()}".
            ", Amount: $amount in market {$market->getQuote()->getSymbol()}{$market->getBase()->getSymbol()}"
        );

        return 0;
    }

    /*
     * @description Calculates the amount for the order, return max between % of user balance and $tradeUsdLimit
     * if $availableBalance is less than $tradeUsdLimit, return $availableBalance
     */
    private function calculateAmount(string $balance, Market $market, string $availableBalance): string
    {
        $desiredAmount = $this->calculateCryptoValue($this->tradeUsdLimit, $market->getBase()->getSymbol());

        if ($availableBalance < $desiredAmount) {
            return (string)BigDecimal::of($availableBalance)
                ->toScale($market->getBase()->getShowSubunit(), RoundingMode::DOWN); // avoid viabtc rounding issues
        }

        $percentageAmount = (string)BigDecimal::of($balance)
            ->multipliedBy($this->tradePercentage)
            ->toScale($market->getBase()->getShowSubunit(), RoundingMode::HALF_UP);

        return max($percentageAmount, $desiredAmount);
    }
}
