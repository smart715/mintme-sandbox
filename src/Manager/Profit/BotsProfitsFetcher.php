<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\User;
use App\Exchange\AbstractOrder;
use App\Exchange\Deal;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Manager\Model\Profit\ProfitBotModel;
use App\Manager\UserManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;

class BotsProfitsFetcher extends AbstractProfitFetcher
{
    private MarketFetcherInterface $marketFetcher;
    private MarketHandlerInterface $marketHandler;
    private UserManagerInterface $userManager;
    private MarketNameConverterInterface $marketNameConverter;
    private array $trackedAccountsEmails;
    private array $coinMarkets;
    public function __construct(
        MarketFactoryInterface $marketFactory,
        MarketFetcherInterface $marketFetcher,
        MarketHandlerInterface $marketHandler,
        UserManagerInterface $userManager,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager,
        array $trackedAccountsEmails
    ) {
        parent::__construct($moneyWrapper, $cryptoRatesFetcher, $translator, $cryptoManager);

        $this->marketFetcher = $marketFetcher;
        $this->marketHandler = $marketHandler;
        $this->userManager = $userManager;
        $this->marketNameConverter = $marketNameConverter;
        $this->trackedAccountsEmails = $trackedAccountsEmails;
        $this->coinMarkets = $marketFactory->getMintMeCoinMarkets();
    }

    public static function profitModel(): string
    {
        return ProfitBotModel::class;
    }

    /** @return array<string, ProfitBotModel[]|AbstractProfitModel[]> */
    public function fetchAll(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array {
        $botsProfits = [];

        foreach ($this->trackedAccountsEmails as $email) {
            /** @var User|null $user */
            $user = $this->userManager->findUserByEmail($email);

            if (null === $user) {
                throw new \InvalidArgumentException('Invalid user email provided');
            }

            $botsProfits[$user->getNickname()] = $this->fetch($startDate, $endDate, $withMintMe, $user->getId());
        }

        return $botsProfits;
    }

    /** @return array ProfitBotModel[]|AbstractProfitModel[] */
    public function fetch(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true,
        ?int $userId = null
    ): array {
        if (null === $userId) {
            throw new \InvalidArgumentException('User id must be provided');
        }

        $profitBotModels = [];

        foreach ($this->coinMarkets as $market) {
            $deals = $this->getUserDeals($userId, $market, $startDate, $endDate);

            if (!$deals) {
                continue;
            }

            [$totalSold, $totalBought, $mintMeSoldProfit, $sellCount, $buyCount] = $this->dealsStats($deals, $market);
            $dealsCount = $sellCount + $buyCount;

            $profitBotModels[] = new ProfitBotModel(
                (string)$market,
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol(),
                $this->moneyWrapper->format($totalSold),
                $this->moneyWrapper->format($totalBought),
                $mintMeSoldProfit = $this->moneyWrapper->format($mintMeSoldProfit),
                $this->calculateUsdValue($mintMeSoldProfit, $market->getBase()->getSymbol()),
                (string)$sellCount,
                (string)$buyCount,
                (string)$dealsCount
            );
        }

        return $this->appendTotalColumn($profitBotModels);
    }

    public function total(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): AbstractProfitModel {
        return $this->getTotalColumn($this->fetchAll($startDate, $endDate, $withMintMe));
    }

    protected function getTotalColumn(array $profits): AbstractProfitModel
    {
        $profits = array_reduce($profits, static fn(array $all, $current) => [...$all, ...$current], []);
        $profits = array_filter($profits, static fn(AbstractProfitModel $p) => 'Total' !== $p->getSymbol());
        $profits = $this->appendTotalColumn($profits);

        return $this->lastArrayElement($profits);
    }

    private function isWEBMarket(Market $market): bool
    {
        return $this->isWEB($market->getBase()->getSymbol()) || $this->isWEB($market->getQuote()->getSymbol());
    }

    /**
     * Parse all user deals for a given period
     *
     * @return Deal[]
     */
    private function getUserDeals(
        int $userId,
        Market $market,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $batchSize = 100;
        $dealsToMerge = []; // less memory usage than array_merge in loop
        $offset = 0;

        do {
            $batchDeals = $this->marketHandler->parseDealsSingleMarket(
                $this->marketFetcher->getUserExecutedHistory(
                    $userId,
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $batchSize,
                    $startDate->getTimestamp(),
                    $endDate->getTimestamp(),
                    false
                ),
                $market,
                false
            );

            $dealsToMerge[] = $batchDeals;

            $offset += $batchSize;
        } while (count($batchDeals) === $batchSize);

        return array_merge(...$dealsToMerge);
    }

    /**
     * @param Deal[] $deals
     * @return array{0: Money, 1: Money, 2: Money, 3: int, 4: int}
     */
    private function dealsStats(array $deals, Market $market): array
    {
        // @phpstan-ignore-next-line - array_reduce returns mixed
        return array_values(array_reduce(
            $deals,
            function (array $summary, Deal $deal): array {
                [$side, $amount] = [
                    AbstractOrder::SELL_SIDE === $deal->getSide() ? 'sell' : 'buy',
                    $deal->getAmount(),
                ];

                $summary[$side] = $summary[$side]->add($amount);
                $summary[$side . 'Count']++;
                $totalCostInBase = $this->moneyWrapper->convertByRatio(
                    $deal->getPrice(),
                    $deal->getMarket()->getBase()->getSymbol(),
                    $this->moneyWrapper->format($deal->getAmount())
                );

                if ($this->isWEBMarket($deal->getMarket())) {
                    $summary['mintMeSoldProfit'] = 'sell' === $side
                        ? $summary['mintMeSoldProfit']->add($totalCostInBase)
                        : $summary['mintMeSoldProfit']->subtract($totalCostInBase);
                }

                return $summary;
            },
            [
                'sell' => new Money(0, new Currency($market->getQuote()->getSymbol())),
                'buy' => new Money(0, new Currency($market->getQuote()->getSymbol())),
                'mintMeSoldProfit' => new Money(0, new Currency($market->getBase()->getSymbol())),
                'sellCount' => 0,
                'buyCount' => 0,
            ]
        ));
    }
}
