<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\Exception\FetchException;
use App\Communications\RestRpcInterface;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Repository\MarketStatusRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class MarketCapCalculator
{
    /** @var array<string> */
    private array $supplyLinks;

    private int $tokenSupply;

    private MarketStatusRepository $repository;

    private MoneyWrapperInterface $moneyWrapper;

    private RestRpcInterface $rpc;

    private FixedExchange $exchange;

    private CryptoRatesFetcherInterface $cryptoRatesFetcher;

    private int $minimumVolumeForMarketcap;
    
    private LoggerInterface $logger;

    public function __construct(
        array $supplyLinks,
        int $tokenSupply,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper,
        RestRpcInterface $rpc,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        LoggerInterface $logger,
        int $minimumVolumeForMarketcap
    ) {
        $this->supplyLinks = $supplyLinks;
        $this->tokenSupply = $tokenSupply;
        $this->moneyWrapper = $moneyWrapper;
        $this->rpc = $rpc;

        $newRepository = $em->getRepository(MarketStatus::class);

        $this->repository = $newRepository;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->minimumVolumeForMarketcap = $minimumVolumeForMarketcap;
        $this->logger = $logger;
    }

    public function calculate(string $base = Symbols::BTC): string
    {
        if (Symbols::USD === $base) {
            # We'll calculate it as if it was BTC, and will convert the final amount to USD.
            # Pretty nice hack, not so obvious, but I liked it
            $calculatingUSD = Symbols::BTC;
        } elseif (!in_array($base, [Symbols::BTC, Symbols::WEB, Symbols::ETH, Symbols::USDC, Symbols::CRO, Symbols::AVAX, Symbols::SOL])) {
            throw new \DomainException('Parameter $base can only be WEB, BTC, ETH, USDC, CRO, AVAX, SOL or USD');
        }

        # Calculate MarketCap for WEB/token markets
        $tokenMarketCap = $this->calculateTokenMarketCap();

        try {
            # Convert to Base
            $tokenMarketCap = $this->moneyWrapper->convert(
                $tokenMarketCap,
                new Currency($base),
                $this->getExchange()
            );
        } catch (\Throwable $exception) {
            if ($exception instanceof FetchException) {
                $this->logger->error(
                    'Set token market cap to zero. FetchException error: ' . $exception->getMessage()
                );

                $tokenMarketCap = new Money('0', new Currency($base));
            } else {
                throw $exception;
            }
        }
        
        # Calculate MarketCap for Base/ExchangeableCryptos
        $marketCap = $this->getExchangeableCryptosMarketCap($calculatingUSD ?? $base);

        # Convert to USD if that's what we want
        if (isset($calculatingUSD)) {
            $marketCap = $this->moneyWrapper->convert(
                $marketCap,
                new Currency(Symbols::USD)
            );
        }

        # Add it to market cap of tokens
        $marketCap = $marketCap->add($tokenMarketCap);

        # Return formatted
        return $this->format($marketCap);
    }

    private function calculateTokenMarketCap(): Money
    {
        $tokenMarkets = $this->repository->getTokenWEBMarkets();
        // do not show market cap for markets with 30d volume of value less than min_web_cap MINTME
        // and for not deployed tokens

        return array_reduce($tokenMarkets, function ($marketCap, $market) {
            return $market->getMonthVolume()->lessThan($this->getMinimumMonthVolume()) ||
                !$market->getQuote()->getDeployed()
                ? $marketCap
                : $market->getLastPrice()->multiply($this->tokenSupply)->add($marketCap);
        }, $this->getZero(Symbols::WEB));
    }

    private function getZero(string $base): Money
    {
        return new Money(0, new Currency($base));
    }

    private function fetchSupplies(array $markets): array
    {
        $supplies = [];
        $unknownCryptos = [];

        foreach ($markets as $market) {
            $name = $market->getQuote()->getName();

            if (isset($supplies[$name]) || isset($unknownCryptos[$name])) {
                continue;
            }

            if (isset($this->supplyLinks[$name])) {
                $supplies[$name] = floatval(file_get_contents($this->supplyLinks[$name]));
            } else {
                $unknownCryptos[strtolower($name)] = true;
            }
        }

        # If we ever add another exchangeable crypto apart from webchain
        if ($unknownCryptos) {
            $response = $this->rpc->send(
                'coins/markets?vs_currency=usd&ids='.implode(',', array_keys($unknownCryptos)).'&order=market_cap_desc&per_page=100&page=1&sparkline=false',
                Request::METHOD_GET
            );

            $response = json_decode($response);

            foreach ($response as $crypto) {
                $supplies[$crypto->name] = $crypto->circulating_supply;
            }
        }

        return $supplies;
    }

    private function getExchangeableCryptosMarketCap(string $base): Money
    {
        $markets = $this->repository->getExchangeableCryptoMarkets();
        $supplies = $this->fetchSupplies($markets);

        return array_reduce($markets, function ($marketCap, $market) use ($base, $supplies) {
            return $market->getCrypto()->getSymbol() === $base
                ? $market->getLastPrice()->multiply($supplies[$market->getQuote()->getName()])->add($marketCap)
                : $marketCap;
        }, $this->getZero($base));
    }

    private function getExchange(): FixedExchange
    {
        if (isset($this->exchange)) {
            return $this->exchange;
        }

        $rates = $this->cryptoRatesFetcher->fetch();
        $rates[Symbols::WEB][Symbols::WEB] = 1;

        return $this->exchange = new FixedExchange($rates);
    }

    private function format(Money $money): string
    {
        return $this->moneyWrapper->format($money);
    }

    private function getMinimumMonthVolume(): Money
    {
        return $this->moneyWrapper->parse((string)$this->minimumVolumeForMarketcap, Symbols::WEB);
    }
}
