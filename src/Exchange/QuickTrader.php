<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\QuickTradeException;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\CheckTradeResult;
use App\Exchange\Trade\TradeResult;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;

class QuickTrader implements QuickTraderInterface
{
    private Exchanger $exchanger;
    private QuickTradeConfig $config;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketHandlerInterface $marketHandler;

    public function __construct(
        Exchanger $exchanger,
        QuickTradeConfig $config,
        MoneyWrapperInterface $moneyWrapper,
        MarketHandlerInterface $marketHandler
    ) {
        $this->exchanger = $exchanger;
        $this->config = $config;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketHandler = $marketHandler;
    }

    public function makeSell(User $user, Market $market, string $amount, string $expectedAmount): TradeResult
    {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote instanceof Token ?
            Symbols::TOK :
            $quote->getSymbol();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, $baseSymbol);

        $checkResult = $this->marketHandler->getExpectedSellResult(
            $market,
            $amount,
            $this->config->getSellFee()
        );

        $amount = $this->moneyWrapper->parse($amount, $quoteSymbol);

        $buyOrdersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();
        $buyOrdersSummary = $this->moneyWrapper->parse($buyOrdersSummary, $quoteSymbol);

        if (!$expectedAmount->equals($checkResult->getExpectedAmount()) ||
            $amount->greaterThan($buyOrdersSummary)
        ) {
            throw QuickTradeException::availabilityChanged();
        }

        return $this->exchanger->executeOrder(
            $user,
            $market,
            $this->moneyWrapper->format($amount),
            $this->moneyWrapper->format($expectedAmount),
            Order::SELL_SIDE,
            $this->config->getSellFee()
        );
    }

    public function checkSell(Market $market, string $amount): CheckTradeResult
    {
        return $this->marketHandler->getExpectedSellResult(
            $market,
            $amount,
            $this->config->getSellFee()
        );
    }
}
