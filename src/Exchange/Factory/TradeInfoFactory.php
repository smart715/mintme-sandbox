<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\TradeInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TradeInfoFactory implements TradeFactoryInterface
{
    private Market $market;
    private BalanceHandlerInterface $balanceHandler;
    private MarketHandlerInterface $marketHandler;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        Market $market,
        BalanceHandlerInterface $balanceHandler,
        MarketHandlerInterface $marketHandler,
        ParameterBagInterface $parameterBag
    ) {
        $this->market = $market;
        $this->balanceHandler = $balanceHandler;
        $this->marketHandler = $marketHandler;
        $this->parameterBag = $parameterBag;
    }

    public function create(): TradeInfo
    {
        $token = $this->market->getQuote();

        $topHolders = $this->balanceHandler->topHolders(
            $token,
            $this->parameterBag->get('top_holders')
        );

        $soldOnMarket = $this->marketHandler->getMarketInfo($this->market)->getSoldOnMarket();
        $volumeDonation = $this->marketHandler->getMarketStatus($this->market)['volumeDonation'];
        $sellSummary = $this->marketHandler->getSellOrdersSummary($this->market);

        $tradeInfo = new TradeInfo(
            $topHolders,
            $volumeDonation,
            $soldOnMarket,
            $sellSummary
        );

        if ($token instanceof Token && $token->isMintmeToken()) {
            $tokenExchange = $this->balanceHandler->exchangeBalance(
                $token->getProfile()->getUser(),
                $token
            );

            $tradeInfo->setTokenExchange($tokenExchange)
                ->setToken($token);
        }

        return $tradeInfo;
    }
}
