<?php declare(strict_types = 1);

namespace App\Manager\Profit;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Manager\Model\Profit\AbstractProfitModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;

abstract class AbstractProfitFetcher
{
    protected MoneyWrapperInterface $moneyWrapper;
    protected CryptoRatesFetcherInterface $cryptoRatesFetcher;
    protected TranslatorInterface $translator;
    protected array $cryptos;

    public function __construct(
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->translator = $translator;

        $allCryptos = $cryptoManager->findAllIndexed('name', false, false);

        $this->cryptos = array_values(array_map(static function (Crypto $crypto) {
            return $crypto->getSymbol();
        }, $allCryptos));
    }

    abstract public static function profitModel(): string;

    /**
     * Fetches profits for given period with a total column appended
     *
     * @return AbstractProfitModel[]
     */
    abstract public function fetch(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): array;

    /**
     * Fetches total profits for given period
     */
    public function total(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $withMintMe = true
    ): AbstractProfitModel {
        return $this->getTotalColumn($this->fetch($startDate, $endDate, $withMintMe));
    }

    /**
     * Calculates USD value of given amount of crypto
     */
    protected function calculateUsdValue(string $amount, string $cryptoSymbol): string
    {
        $rates = $this->cryptoRatesFetcher->fetch();
        $price = $rates[$cryptoSymbol][Symbols::USD];

        return $this->moneyWrapper->format($this->moneyWrapper->parse((string)($amount * $price), Symbols::USD));
    }

    /**
     * Format decimal notation to money formatted string
     */
    protected function formatMoneyWithNotation(string $notation, string $crypto): string
    {
        return $this->moneyWrapper->format(
            new Money(
                $this->moneyWrapper->convertToDecimalIfNotation($notation, $crypto),
                new Currency($crypto)
            )
        );
    }

    /**
     * Appends total column to given profits
     *
     * @param AbstractProfitModel[] $profits
     * @return AbstractProfitModel[]
     */
    protected function appendTotalColumn(array $profits): array
    {
        /** @var AbstractProfitModel $profitModel */
        $profitModel = static::profitModel();

        $summaryColumnByUsd = array_reduce(
            $profits,
            function (AbstractProfitModel $summary, AbstractProfitModel $profit): AbstractProfitModel {
                return $this->accumulateSummary($summary, $profit);
            },
            new $profitModel($this->translator->trans('total'))
        );

        return [...$profits, $summaryColumnByUsd];
    }

    protected function accumulateSummary(AbstractProfitModel $summary, AbstractProfitModel $profit): AbstractProfitModel
    {
        return $summary
            ->setCount(bcadd($summary->getCount(), $profit->getCount()))
            ->setProfitInUsd(bcadd($summary->getProfitInUsd(), $profit->getProfitInUsd(), 2));
    }

    protected function isWEB(string $symbol): bool
    {
        return Symbols::WEB === $symbol || Symbols::MINTME === $symbol;
    }

    protected function getTotalColumn(array $profits): AbstractProfitModel
    {
        return $this->lastArrayElement($profits);
    }

    protected function lastArrayElement(array $profits): AbstractProfitModel
    {
        return array_slice($profits, -1)[0];
    }
}
