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
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        ValidatorFactoryInterface $validatorFactory
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
    }

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
            return new TradeResult(TradeResult::INSUFFICIENT_BALANCE);
        }

        if (!$this->vf->createOrderValidator($market, $priceInput, $amountInput)->validate()) {
            return new TradeResult(TradeResult::SMALL_AMOUNT);
        }

        $price = $this->mw->parse(
            $this->parseAmount($priceInput, $market, true),
            $this->getSymbol($market->getQuote())
        );

        if ($marketPrice) {
            /** @var Order[] $orders */
            $orders = $this->getPendingOrders($market, $isSellSide ? Order::BUY_SIDE : Order::SELL_SIDE);

            if ($orders) {
                $price = $orders[0]->getPrice();
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
            $user->getReferrencer() ? (int)$user->getReferrencer()->getId() : 0
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

    private function parseAmount(string $amount, Market $market, bool $useBase = false): string
    {
        /** @var Crypto $crypto */
        $crypto = $useBase ?
            $market->getBase() :
            $market->getQuote();

        return bcdiv($amount, '1', $market->isTokenMarket() ?
            $this->bag->get('token_precision') :
            $crypto->getShowSubunit());
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }

    /** @return array<Order> */
    private function getPendingOrders(Market $market, int $side): array
    {
        return Order::BUY_SIDE === $side ?
            $this->mh->getPendingBuyOrders($market, 0, 1) :
            $this->mh->getPendingSellOrders($market, 0, 1);
    }

    private function exceedAvailableReleased(
        User $user,
        string $token,
        string $amount
    ): bool {
        $token = $this->tm->findByName($token);
        $profile = $token->getProfile();

        if ($profile && $user === $profile->getUser()) {
            /** @var BalanceView $balanceViewer */
            $balanceViewer = $this->bvf->create(
                $this->bh->balances($user, [$token])
            )[$token->getSymbol()];

            return $this->mw
                ->parse($amount, MoneyWrapper::TOK_SYMBOL)
                ->greaterThan($balanceViewer->getAvailable());
        }

        return false;
    }
}
