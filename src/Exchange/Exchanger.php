<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Communications\Exception\FetchException;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

class Exchanger implements ExchangerInterface
{
    private TraderInterface $trader;
    private MoneyWrapperInterface $mw;
    private MarketAMQPInterface $mp;
    private BalanceHandlerInterface $bh;
    private BalanceViewFactoryInterface $bvf;
    private UserActionLogger $logger;
    private ParameterBagInterface $bag;
    private MarketHandlerInterface $mh;
    private TokenManagerInterface $tm;
    private ValidatorFactoryInterface $vf;
    private TranslatorInterface $translator;
    private LimitOrderConfig $limitOrderConfig;

    public function __construct(
        TraderInterface $trader,
        MoneyWrapperInterface $moneyWrapper,
        MarketAMQPInterface $marketProducer,
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactoryInterface $balanceViewFactory,
        UserActionLogger $userActionLogger,
        ParameterBagInterface $bag,
        MarketHandlerInterface $marketHandler,
        TokenManagerInterface $tokenManager,
        ValidatorFactoryInterface $validatorFactory,
        TranslatorInterface $translator,
        LimitOrderConfig $limitOrderConfig
    ) {
        $this->trader = $trader;
        $this->mw = $moneyWrapper;
        $this->mp = $marketProducer;
        $this->bh = $balanceHandler;
        $this->bvf = $balanceViewFactory;
        $this->logger = $userActionLogger;
        $this->bag = $bag;
        $this->mh = $marketHandler;
        $this->tm = $tokenManager;
        $this->vf = $validatorFactory;
        $this->translator = $translator;
        $this->limitOrderConfig = $limitOrderConfig;
    }

    public function cancelOrder(Market $market, Order $order): TradeResult
    {
        $tradeResult = $this->trader->cancelOrder($order);

        try {
            $this->mp->send($market);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to update '${market}' market status. Reason: {$exception->getMessage()}"
            );
        }

        $this->logger->info(
            sprintf('Cancel %s order', Order::BUY_SIDE === $order->getSide() ? 'buy' : 'sell'),
            [
                'base' => $market->getBase()->getSymbol(),
                'quote' => $market->getQuote()->getSymbol(),
            ]
        );

        return $tradeResult;
    }

    /**
     * @throws \Exception
     */
    public function placeOrder(
        User $user,
        Market $market,
        string $amountInput,
        string $priceInput,
        bool $marketPrice,
        int $side
    ): TradeResult {
        $isSellSide = Order::SELL_SIDE === $side;

        if ($isSellSide && $this->exceedAvailableReleased(
            $user,
            $market->getQuote()->getSymbol(),
            $amountInput
        )) {
            return new TradeResult(TradeResult::INSUFFICIENT_BALANCE, $this->translator);
        }

        $price = $this->mw->parse(
            $this->parseAmount($priceInput, $market, true),
            $this->getSymbol($market->getBase())
        );

        if ($marketPrice) {
            try {
                $price = $this->getMarketPrice($market, $user, $isSellSide);
            } catch (\Throwable $e) {
                return new TradeResult(TradeResult::FAILED, $this->translator);
            }
        }

        $amount = $this->mw->parse(
            $this->parseAmount($amountInput, $market),
            $this->getSymbol($market->getQuote())
        );

        $userFee = $user->getTradingFee() ?? $this->limitOrderConfig->getFeeRateByMarket($market);

        $fee = $this->mw->parse(
            $userFee,
            $this->getSymbol($market->getQuote())
        );

        $totalPrice = $price->multiply($amountInput);

        $isBuySide = Order::BUY_SIDE === $side;
        $possibleMatchingSellOrders = $isBuySide
             ? $this->mh->getPendingSellOrders($market)
             : [];

        $minValidators = [
            $this->vf->createMinTradableValidator($market->getBase(), $market, $priceInput),
            $this->vf->createMinTradableValidator($market->getQuote(), $market, $amountInput),
            $this->vf->createOrderMinUsdValidator(
                $market->getBase(),
                $price,
                $amount,
                $isBuySide,
                $possibleMatchingSellOrders
            ),
            $this->vf->createMinAmountValidator($market->getQuote(), $amountInput),
            $this->vf->createMinTradableValidator($market->getBase(), $market, $this->mw->format($totalPrice)),
            $this->vf->createMinUsdValidator($market->getBase(), $this->mw->format($totalPrice)),
        ];

        foreach ($minValidators as $validator) {
            try {
                if (!$validator->validate()) {
                    return new TradeResult(
                        TradeResult::SMALL_AMOUNT,
                        $this->translator,
                        $validator->getMessage()
                    );
                }
            } catch (FetchException $e) {
                $this->logger->error('Failed to fetch min amount', [
                    'base' => $market->getBase()->getSymbol(),
                    'quote' => $market->getQuote()->getSymbol(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $order = new Order(
            null,
            $user,
            null,
            $market,
            $amount,
            $side,
            $price,
            Order::PENDING_STATUS,
            $fee,
            null,
            null,
            $user->getReferencer() ? (int)$user->getReferencer()->getId() : 0
        );

        $tradeResult = $this->trader->placeOrder($order);

        try {
            $this->mp->send($market, $user);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to update '${market}' market status. Reason: {$exception->getMessage()}"
            );
        }

        $this->logger->info(
            sprintf('Create %s order', $isBuySide ? 'buy' : 'sell'),
            [
                'base' => $market->getBase()->getSymbol(),
                'quote' => $market->getQuote()->getSymbol(),
                'amount' => $amount->getAmount(),
                'price' => $price->getAmount(),
            ]
        );

        return $tradeResult;
    }

    public function executeOrder(
        User $user,
        Market $market,
        string $value,
        int $side,
        ?string $fee = null,
        bool $updateTokenOrCrypto = true
    ): TradeResult {
        $isSellSide = Order::SELL_SIDE === $side;

        if ($isSellSide && $this->exceedAvailableReleased($user, $market->getQuote()->getSymbol(), $value)) {
            return new TradeResult(TradeResult::INSUFFICIENT_BALANCE, $this->translator);
        }

        // In order to use the same Order class, we use amount and price logic
        $amount = $this->mw->parse(
            $isSellSide ? $this->parseAmount($value, $market) : '0',
            $this->getSymbol($market->getQuote())
        );

        $price = $this->mw->parse(
            $isSellSide ? '0' : $this->parseAmount($value, $market, true),
            $market->getBase()->getSymbol()
        );

        $userFee = $user->getTradingFee() ?? $this->limitOrderConfig->getFeeRateByMarket($market);

        $fee = $fee ?? $userFee;

        $fee = $this->mw->parse(
            $fee,
            $this->getSymbol($market->getQuote())
        );

        $order = new Order(
            null,
            $user,
            null,
            $market,
            $amount,
            $side,
            $price,
            Order::PENDING_STATUS,
            $fee,
            null,
            null,
            $user->getReferencer() ? (int)$user->getReferencer()->getId() : 0
        );

        $tradeResult = $this->trader->executeOrder($order, $updateTokenOrCrypto);

        try {
            $this->mp->send($market, $user);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to update '${market}' market status. Reason: {$exception->getMessage()}"
            );
        }

        $this->logger->info(
            sprintf('Excecute %s order', Order::BUY_SIDE === $side ? 'buy' : 'sell'),
            [
                'base' => $market->getBase()->getSymbol(),
                'quote' => $market->getQuote()->getSymbol(),
                'value' => $value,
            ]
        );

        return $tradeResult;
    }

    private function parseAmount(string $amount, Market $market, bool $useBase = false): string
    {
        /** @var Crypto $crypto */
        $crypto = $useBase ?
            $market->getBase() :
            $market->getQuote();

        $scale = $crypto->getShowSubunit();

        if ($market->isTokenMarket()) {
            /** @var Token $quote */
            $quote = $market->getQuote();

            $scale = $quote->getPriceDecimals()
                ?: (Symbols::WEB === $crypto->getSymbol()
                    ? (int)$this->bag->get('token_precision')
                    : $crypto->getShowSubunit());
        }

        /** @var string $amount */
        $amount = (string) BigDecimal::of($amount)->dividedBy(
            '1',
            $scale,
            RoundingMode::HALF_DOWN
        );

        return $amount;
    }

    private function getSymbol(TradableInterface $tradable): string
    {
        return $tradable instanceof Token
            ? Symbols::TOK
            : $tradable->getSymbol();
    }

    /** @return array<Order> */
    private function getPendingOrders(Market $market, int $side, int $offset): array
    {
        return Order::BUY_SIDE === $side ?
            $this->mh->getPendingBuyOrders($market, $offset) :
            $this->mh->getPendingSellOrders($market, $offset);
    }

    private function exceedAvailableReleased(
        User $user,
        string $tokenName,
        string $amount
    ): bool {
        /** @var Token|null $token */
        $token = $this->tm->findByName($tokenName);

        if (!$token) {
            return false;
        }

        $profile = $token->getProfile();

        if ($profile && $user->getId() === $profile->getUser()->getId()) {
            /** @var BalanceView $balanceViewer */
            $balanceViewer = $this->bvf->create(
                [$token],
                $this->bh->balances($user, [$token]),
                $user
            )[$token->getSymbol()];

            return $this->mw
                ->parse($amount, Symbols::TOK)
                ->greaterThan($balanceViewer->getAvailable());
        }

        return false;
    }

    private function getMarketPrice(Market $market, User $user, bool $isSellSide): Money
    {
        $base = $market->getBase();
        $quote = $market->getQuote();

        $balance = $this->getBalance(
            $user,
            $isSellSide ? $quote : $base
        );

        $ordersQuoteAmountSum = new Money(0, new Currency($this->getSymbol($quote)));

        $offset = 0;
        $orders = [];

        $marketPrice = null;

        do {
            $offset += count($orders);
            $moreOrders = $this->getPendingOrders(
                $market,
                $isSellSide ? Order::BUY_SIDE : Order::SELL_SIDE,
                $offset
            );

            // This is so that $orders will keep the last orders if $moreOrders comes empty
            // so that price can be set to last order's price if the market price is still null after loop
            $orders = count($moreOrders)
                ? $moreOrders
                : $orders;

            foreach ($moreOrders as $order) {
                $ordersQuoteAmountSum = $ordersQuoteAmountSum->add($order->getAmount());

                $totalPrice = $order->getPrice()->multiply($this->mw->format($ordersQuoteAmountSum));

                $condition = $balance->lessThanOrEqual(
                    $isSellSide ? $ordersQuoteAmountSum : $totalPrice
                );

                if ($condition) {
                    $marketPrice = $order->getPrice();

                    break;
                }
            }

            if ($marketPrice) {
                break;
            }
        } while ($moreOrders);

        $count = count($orders);

        if (null === $marketPrice && $count > 0) {
            $marketPrice = $orders[$count - 1]->getPrice();
        } elseif (null === $marketPrice) {
            throw new \Exception('Market price selected when market price is 0');
        }

        return $marketPrice;
    }

    private function getBalance(User $user, TradableInterface $tradable): Money
    {
        $balanceResult = $this->bh->balance($user, $tradable);

        if ($tradable instanceof Token) {
            return $this->tm->getRealBalance($tradable, $balanceResult, $user)->getAvailable();
        }

        return $balanceResult->getAvailable();
    }
}
