<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Communications\RestRpcInterface;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Manager\CryptoManagerInterface;
use App\Repository\MarketStatusRepository;
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

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(
        array $supplyLinks,
        int $tokenSupply,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper,
        RestRpcInterface $rpc,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->supplyLinks = $supplyLinks;
        $this->tokenSupply = $tokenSupply;
        $this->moneyWrapper = $moneyWrapper;
        $this->rpc = $rpc;
        $this->cryptoManager = $cryptoManager;
        $this->repository = $em->getRepository(MarketStatus::class);
    }

    public function calculate(string $base = 'BTC'): string
    {
        $crypto = $this->cryptoManager->findBySymbol($base);

        if ('USD' === $base) {
            # We'll calculate it as if it was BTC, and will convert the final amount to USD
            $base = 'BTC';
            $calculatingUSD = true;
        } elseif (null === $crypto || !$crypto->isTradable()) {
            throw new \Exception('Parameter base should be a valid tradable crypto or USD');
        }

        # Calculate MarketCap for token/WEB markets
        $tokenMarketCap = $this->calculateTokenMarketCap();

        # Convert to Base
        $tokenMarketCap = $this->moneyWrapper->convert(
            $tokenMarketCap,
            new Currency($base),
            new FixedExchange([
                'WEB' => [
                    $base => $this->getWEBBasePrice($base),
                ],
            ])
        );

        # Add it to WEBMarketCap
        $marketCap = $this->getExchangeableCryptosMarketCap($base)->add($tokenMarketCap);

        if (isset($calculatingUSD)) {
            $response = $this->rpc->send('simple/price?ids=bitcoin&vs_currencies=usd', Request::METHOD_GET);
            $response = json_decode($response, true);

            $marketCap = $this->moneyWrapper->convert(
                $marketCap,
                new Currency('USD'),
                new FixedExchange([
                    'BTC' => [
                        'USD' => $response['bitcoin']['usd'],
                    ],
                ])
            );
        }

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

    private function getWEBBasePrice(string $base): string
    {
        if (Token::WEB_SYMBOL === $base) {
            return '1';
        }

        return $this->format($this->repository->findByBaseQuoteNames($base, Token::WEB_SYMBOL)->getLastPrice());
    }

    private function format(Money $money): string
    {
        return $this->moneyWrapper->format($money);
    }
}
