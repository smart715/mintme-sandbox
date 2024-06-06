<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Communications\Exception\FetchException;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\QuickTradeException;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\CheckTradeResult;
use App\Exchange\Trade\TradeResult;
use App\Logger\UserActionLogger;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class QuickTrader implements QuickTraderInterface
{
    private ExchangerInterface $exchanger;

    private QuickTradeConfig $config;

    private MoneyWrapperInterface $moneyWrapper;

    private MarketHandlerInterface $marketHandler;

    private ValidatorFactoryInterface $validatorFactory;

    private UserActionLogger $logger;

    public function __construct(
        ExchangerInterface $exchanger,
        QuickTradeConfig $config,
        MoneyWrapperInterface $moneyWrapper,
        MarketHandlerInterface $marketHandler,
        ValidatorFactoryInterface $validatorFactory,
        UserActionLogger $logger
    ) {
        $this->exchanger = $exchanger;
        $this->config = $config;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketHandler = $marketHandler;
        $this->validatorFactory = $validatorFactory;
        $this->logger = $logger;
    }

    public function makeSell(User $user, Market $market, string $amount, string $expectedAmount): TradeResult
    {
        $this->checkMinimum($market->getBase(), $market, $expectedAmount);

        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote->getMoneySymbol();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, $baseSymbol);

        $checkResult = $this->marketHandler->getExpectedSellResult(
            $market,
            $amount,
            $this->config->getSellFeeByMarket($market)
        );

        $amount = $this->moneyWrapper->parse($amount, $quoteSymbol);

        $buyOrdersSummary = $this->marketHandler->getBuyOrdersSummary($market)->getQuoteAmount();
        $buyOrdersSummary = $this->moneyWrapper->parse($buyOrdersSummary, $quoteSymbol);

        $difference = $expectedAmount->subtract($checkResult->getExpectedAmount())->absolute();

        $maxError = $this->moneyWrapper->parse('0.0001', $baseSymbol);

        if ($difference->greaterThan($maxError)
            || $amount->greaterThan($buyOrdersSummary)
        ) {
            throw QuickTradeException::availabilityChanged();
        }

        return $this->exchanger->executeOrder(
            $user,
            $market,
            $this->moneyWrapper->format($amount),
            Order::SELL_SIDE,
            $this->config->getSellFeeByMarket($market)
        );
    }

    public function makeBuy(User $user, Market $market, string $amount, string $expectedAmount): TradeResult
    {
        $this->checkMinimum($market->getBase(), $market, $amount);

        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote->getSymbol();

        $expectedAmount = $this->moneyWrapper->parse($expectedAmount, $quoteSymbol);

        $buyCryptoFee = $this->config->getBuyFeeByMarket($market);

        $checkResult = $this->marketHandler->getExpectedBuyResult(
            $market,
            $amount,
            $buyCryptoFee
        );

        $amount = $this->moneyWrapper->parse($amount, $baseSymbol);

        $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market)->getBaseAmount();
        $sellOrdersSummary = $this->moneyWrapper->parse($sellOrdersSummary, $baseSymbol);

        $difference = $expectedAmount->subtract($checkResult->getExpectedAmount())->absolute();
        $maxError = $this->moneyWrapper->parse('0.0001', $quoteSymbol);

        if ($difference->greaterThan($maxError)
            || $amount->greaterThan($sellOrdersSummary)
        ) {
            throw QuickTradeException::availabilityChanged();
        }

        return $this->exchanger->executeOrder(
            $user,
            $market,
            $this->moneyWrapper->format($amount),
            Order::BUY_SIDE,
            $buyCryptoFee
        );
    }

    public function checkSell(Market $market, string $amount): CheckTradeResult
    {
        return $this->marketHandler->getExpectedSellResult(
            $market,
            $amount,
            $this->config->getSellFeeByMarket($market)
        );
    }

    public function checkSellReversed(Market $market, string $amountToReceive): CheckTradeResult
    {
        return $this->marketHandler->getExpectedSellReversedResult(
            $market,
            $amountToReceive,
            $this->config->getSellFeeByMarket($market)
        );
    }

    public function checkBuy(Market $market, string $amount): CheckTradeResult
    {
        return $this->marketHandler->getExpectedBuyResult(
            $market,
            $amount,
            $this->config->getBuyFeeByMarket($market)
        );
    }

    public function checkDonationReversed(Market $market, string $amountToReceive): CheckTradeResult
    {
        return $this->marketHandler->getExpectedDonationReversedResult(
            $market,
            $amountToReceive,
            $this->config->getBuyTokenFee()
        );
    }

    public function checkBuyReversed(Market $market, string $amountToReceive): CheckTradeResult
    {
        return $this->marketHandler->getExpectedBuyReversedResult(
            $market,
            $amountToReceive,
            $this->config->getBuyFeeByMarket($market)
        );
    }

    /**
     * @throws QuickTradeException
     */
    private function checkMinimum(TradableInterface $tradable, Market $market, string $amount): void
    {
        $minimum = $this->config->getMinAmountBySymbol($tradable->getMoneySymbol());
        $minimum = $this->moneyWrapper->format($minimum);

        $minValidators = [
            $this->validatorFactory->createMinTradableValidator($tradable, $market, $amount, $minimum),
            $this->validatorFactory->createMinUsdValidator($tradable, $amount),
        ];

        foreach ($minValidators as $validator) {
            /** @var ValidatorInterface $validator */
            try {
                $validate = $validator->validate();
            } catch (FetchException $e) {
                $this->logger->error('[QuickTrader] Failed to validate minimum trade' . $e->getMessage());

                continue;
            }

            if (!$validate) {
                throw QuickTradeException::minAmountValidator($validator->getMessage());
            }
        }
    }
}
