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

    /** @var MarketStatus */
    private $WEBBTCMarket;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        int $tokenSupply,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->tokenSupply = $tokenSupply;
        $this->moneyWrapper = $moneyWrapper;

        /** @var MarketStatusRepository $repository */
        $repository = $em->getRepository(MarketStatus::class);
        $this->repository = $repository;
        $this->WEBBTCMarket = $repository->findByBaseQuoteNames(Token::BTC_SYMBOL, Token::WEB_SYMBOL);
    }

    public function calculate(): string
    {
        # Calculate MarketCap for token/WEB markets
        $tokenMarketCap = $this->calculateTokenMarketCap();

        # Convert to BTC
        $tokenMarketCap = $this->moneyWrapper->convert(
            $tokenMarketCap,
            new Currency(Token::BTC_SYMBOL),
            new FixedExchange([
                'WEB' => [
                    'BTC' => $this->format($this->WEBBTCMarket->getLastPrice()),
                ],
            ])
        );

        # Add it to WEBMarketCap
        $marketCap = $this->getWEBMarketCap()->add($tokenMarketCap);

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

    private function getWEBSupply(): float
    {
        return floatval(file_get_contents("https://webchain.network/supply.txt"));
    }

    private function getWEBMarketCap(): Money
    {
        return $this->WEBBTCMarket->getLastPrice()->multiply($this->getWEBSupply());
    }

    private function format(Money $money): string
    {
        return $this->moneyWrapper->format($money);
    }
}
