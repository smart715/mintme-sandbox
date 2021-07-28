<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class Exchanger implements ExchangerInterface
{
    /** @var TraderInterface */
    private $trader;

    /** @var MoneyWrapperInterface */
    private $mw;

    /** @var MarketAMQPInterface */
    private $mp;

    /** @var BalanceHandlerInterface */
    private $bh;

    /** @var BalanceViewFactoryInterface */
    private $bvf;

    /** @var UserActionLogger */
    private $logger;

    /** @var ParameterBagInterface */
    private $bag;

    /** @var MarketHandlerInterface */
    private $mh;

    /** @var TokenManagerInterface */
    private $tm;

    /** @var ValidatorFactoryInterface */
    private $vf;

    /** @var TranslatorInterface */
    private $translator;

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
        TranslatorInterface $translator
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
            sprintf('Cancel %s order', 'sell'),
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

        $minOrderValidator = $this->vf->createOrderValidator(
            $market,
            $priceInput,
            $amountInput
        );

        if (!$minOrderValidator->validate()) {
            return new TradeResult(
                TradeResult::SMALL_AMOUNT,
                $this->translator,
                $minOrderValidator->getMessage()
            );
        }

        $price = $this->mw->parse(
            $this->parseAmount($priceInput, $market, true),
            $this->getSymbol($market->getQuote())
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

        $fee = $this->mw->parse(
            (string)($isSellSide ? $this->bag->get('maker_fee_rate') : $this->bag->get('taker_fee_rate')),
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

        $tradeResult = $this->trader->placeOrder($order);

        try {
            $this->mp->send($market);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to update '${market}' market status. Reason: {$exception->getMessage()}"
            );
        }

        $this->logger->info(
            sprintf('Create %s order', Order::BUY_SIDE === $side ? 'buy' : 'sell'),
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
        string $amountInput,
        string $expectedToReceive,
        int $side,
        ?string $fee = null
    ): TradeResult {
        $isSellSide = Order::SELL_SIDE === $side;

        if ($isSellSide && $this->exceedAvailableReleased($user, $market->getQuote()->getSymbol(), $amountInput)) {
            return new TradeResult(TradeResult::INSUFFICIENT_BALANCE, $this->translator);
        }

        $avgPrice = $this->mw->parse(
            $expectedToReceive,
            $market->getBase()->getSymbol()
        )->divide($amountInput);

        $minOrderValidator = $this->vf->createOrderValidator(
            $market,
            $this->mw->format($avgPrice),
            $amountInput
        );

        if (!$minOrderValidator->validate()) {
            return new TradeResult(
                TradeResult::SMALL_AMOUNT,
                $this->translator,
                $minOrderValidator->getMessage()
            );
        }

        $amount = $this->mw->parse(
            $this->parseAmount($amountInput, $market),
            $this->getSymbol($market->getQuote())
        );

        $fee = $fee ?? (string)$this->bag->get($isSellSide ? 'maker_fee_rate' : 'taker_fee_rate');

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
            $avgPrice,
            Order::PENDING_STATUS,
            $fee,
            null,
            null,
            $user->getReferencer() ? (int)$user->getReferencer()->getId() : 0
        );

        $tradeResult = $this->trader->executeOrder($order);

        try {
            $this->mp->send($market);
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
                'amount' => $amount->getAmount(),
                'received' => $expectedToReceive,
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

        /** @var string $amount */
        $amount = (string) BigDecimal::of($amount)->dividedBy(
            '1',
            $market->isTokenMarket()
                ? $this->bag->get('token_precision')
                : $crypto->getShowSubunit(),
            RoundingMode::HALF_DOWN
        );

        return $amount;
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? Symbols::TOK
            : $tradeble->getSymbol();
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

        /** @var Token $token */
        $token = $this->tm->findByName($tokenName);
        $profile = $token ? $token->getProfile() : false;

        if ($profile && $user === $profile->getUser()) {
            /** @var BalanceView $balanceViewer */
            $balanceViewer = $this->bvf->create(
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

    private function getBalance(User $user, TradebleInterface $tradeble): Money
    {
        $balanceResult = $this->bh->balance($user, $tradeble);

        if ($tradeble instanceof Token) {
            return $this->tm->getRealBalance($tradeble, $balanceResult, $user)->getAvailable();
        }

        return $balanceResult->getAvailable();
    }
}
