<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Communications\RestRpcInterface;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Repository\MarketStatusRepository;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Converter;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

class MarketCapCalculator
{
    /** @var array<string> */
    private $supplyLinks;

    /** @var int */
    private $tokenSupply;

    /** @var MarketStatusRepository */
    private $repository;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var RestRpcInterface */
    private $rpc;

    /** @var FixedExchange */
    private $exchange;

    public function __construct(
        array $supplyLinks,
        int $tokenSupply,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper,
        RestRpcInterface $rpc
    ) {
        $this->supplyLinks = $supplyLinks;
        $this->tokenSupply = $tokenSupply;
        $this->moneyWrapper = $moneyWrapper;
        $this->rpc = $rpc;
        $this->repository = $em->getRepository(MarketStatus::class);
    }

    public function calculate(string $base = Token::BTC_SYMBOL): string
    {
        if (MoneyWrapper::USD_SYMBOL === $base) {
            # We'll calculate it as if it was BTC, and will convert the final amount to USD. Pretty nice hack, not so obvious, but I liked it
            $calculatingUSD = Token::BTC_SYMBOL;
        } elseif (Token::BTC_SYMBOL !== $base && Token::WEB_SYMBOL !== $base) {
            throw new \DomainException('Parameter $base can only be WEB, BTC or USD');
        }

        # Calculate MarketCap for WEB/token markets
        $tokenMarketCap = $this->calculateTokenMarketCap();

        # Convert to Base
        $tokenMarketCap = $this->moneyWrapper->convert(
            $tokenMarketCap,
            new Currency($base),
            $this->getExchange()
        );

        # Calculate MarketCap for Base/ExchangeableCryptos
        $marketCap = $this->getExchangeableCryptosMarketCap($calculatingUSD ?? $base);

        # Convert to USD if that's what we want
        if (isset($calculatingUSD)) {
            $marketCap = $this->moneyWrapper->convert(
                $marketCap,
                new Currency(MoneyWrapper::USD_SYMBOL)
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

        return array_reduce($tokenMarkets, function ($marketCap, $market) {
            return $market->getLastPrice()->multiply($this->tokenSupply)->add($marketCap);
        }, $this->getZero(Token::WEB_SYMBOL));
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

        $response = $this->rpc->send('simple/price?ids=webchain,bitcoin&vs_currencies=usd,btc', Request::METHOD_GET);
        $response = json_decode($response, true);

        return $this->exchange = new FixedExchange([
            Token::BTC_SYMBOL => [
                MoneyWrapper::USD_SYMBOL => $response['bitcoin']['usd'],
            ],
            Token::WEB_SYMBOL => [
                MoneyWrapper::USD_SYMBOL => $response['webchain']['usd'],
                Token::BTC_SYMBOL => $response['webchain']['btc'],
                Token::WEB_SYMBOL => 1,
            ],
        ]);
    }

    private function format(Money $money): string
    {
        return $this->moneyWrapper->format($money);
    }
}
