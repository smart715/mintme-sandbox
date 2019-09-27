<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Repository\MarketStatusRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Converter;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;

class MarketCapCalculator
{
    /** @var int */
    private $tokenSupply;

    /** @var MarketStatusRepository */
    private $repository;

    /** @var MarketStatus|null */
    private $WEBBTCMarket = null;

    /** @var FixedExchange|null */
    private $exchange = null;

    /** @var Converter|null */
    private $converter = null;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        int $tokenSupply,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->tokenSupply = $tokenSupply;
        $this->repository = $em->getRepository(MarketStatus::class);
        $this->moneyWrapper = $moneyWrapper;
    }

    public function calculate(): string
    {
        # Calculate MarketCap for token/WEB markets
        $tokenMarketCap = $this->calculateTokenMarketCap();

        # Convert to BTC
        $tokenMarketCap = $this->getConverter()->convert($tokenMarketCap, new Currency(Token::BTC_SYMBOL));

        # Add it to WEBMarketCap
        $marketCap = $this->getWEBMarketCap()->add($tokenMarketCap);

        # Return formatted
        return $this->format($marketCap);
    }

    private function calculateTokenMarketCap(): Money
    {
        $tokenMarkets = $this->repository->getTokenMarkets();

        return array_reduce($tokenMarkets, function ($marketCap, $market) {
            return $market->getLastPrice()->multiply($this->tokenSupply)->add($marketCap);
        }, $this->getZero(Token::WEB_SYMBOL));
    }

    private function getZero(string $base): Money
    {
        return new Money(0, new Currency($base));
    }

    private function getWEBSupply(): float
    {
        return floatval(file_get_contents("https://webchain.network/supply.txt"));
    }

    private function getWEBMarketCap(): Money
    {
        $market = $this->getWEBBTCMarket();

        return $market->getLastPrice()->multiply($this->getWEBSupply());
    }

    private function getWEBBTCMarket(): MarketStatus
    {
        return $this->WEBBTCMarket ?? $this->WEBBTCMarket = $this->repository->findByBaseQuoteNames(Token::BTC_SYMBOL, Token::WEB_SYMBOL);
    }

    private function getExchange(): FixedExchange
    {
        return $this->exchange ?? $this->exchange = new FixedExchange([
            'WEB' => [
                'BTC' => floatval($this->moneyWrapper->format($this->getWEBBTCMarket()->getLastPrice())),
            ],
        ]);
    }

    private function getConverter(): Converter
    {
        return $this->converter ?? new Converter($this->moneyWrapper->getRepository(), $this->getExchange());
    }

    private function format(Money $money): string
    {
        return $this->moneyWrapper->format($money);
    }
}
