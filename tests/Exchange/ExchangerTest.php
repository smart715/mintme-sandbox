<?php declare(strict_types = 1);

namespace App\Tests\Exchange;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Exchanger;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use App\Tests\MockMoneyWrapper;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testPlaceOrderInsufficientBalance(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(MoneyWrapper::TOK_SYMBOL, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->never()),
            $this->mockBalanceHandler($this->once(), $user, $tok),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(1))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok),
            $this->mockValidator(true)
        );
        $result = $exchanger->placeOrder($user, $this->mockMarket(
            $this->mockCrypto('WEB'),
            $tok,
            true
        ), '4', '5', false, Order::SELL_SIDE);

        $this->assertEquals(TradeResult::INSUFFICIENT_BALANCE, $result->getResult());
    }

    public function testPlaceOrderSmallAmount(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(MoneyWrapper::TOK_SYMBOL, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->never()),
            $this->mockBalanceHandler($this->once(), $user, $tok),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok),
            $this->mockValidator(false)
        );
        $result = $exchanger->placeOrder($user, $this->mockMarket(
            $this->mockCrypto('WEB'),
            $tok,
            true
        ), '4', '5', false, Order::SELL_SIDE);

        $this->assertEquals(TradeResult::SMALL_AMOUNT, $result->getResult());
    }

    public function testPlaceOrderSuccess(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(MoneyWrapper::TOK_SYMBOL, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->once()),
            $this->mockBalanceHandler($this->once(), $user, $tok),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok),
            $this->mockValidator(true)
        );
        $result = $exchanger->placeOrder($user, $this->mockMarket(
            $this->mockCrypto('WEB'),
            $tok,
            true
        ), '4', '5', false, Order::SELL_SIDE);

        $this->assertEquals($tradeResult, $result);
    }

    public function testPlaceOrderSuccessMarketPrice(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(MoneyWrapper::TOK_SYMBOL, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->once()),
            $this->mockBalanceHandler($this->once(), $user, $tok),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok),
            $this->mockValidator(true)
        );
        $result = $exchanger->placeOrder($user, $this->mockMarket(
            $this->mockCrypto('WEB'),
            $tok,
            true
        ), '4', '5', true, Order::SELL_SIDE);

        $this->assertEquals($tradeResult, $result);
    }

    private function mockValidator(bool $res): ValidatorFactoryInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($res);
        $validatorFactory = $this->createMock(ValidatorFactoryInterface::class);
        $validatorFactory->method('createOrderValidator')->willReturn($validator);

        return $validatorFactory;
    }

    private function money(int $amount): Money
    {
        return new Money($amount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    private function mockTrader(TradeResult $result): TraderInterface
    {
        $trader = $this->createMock(TraderInterface::class);
        $trader->method('placeOrder')->willReturn($result);

        return $trader;
    }

    private function mockMarketProducer(InvokedCount $count): MarketAMQPInterface
    {
        $producer = $this->createMock(MarketAMQPInterface::class);
        $producer->expects($count)->method('send');

        return $producer;
    }

    private function mockBalanceHandler(
        InvokedCount $count,
        User $user,
        Token $token
    ): BalanceHandlerInterface {
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->expects($count)->method('balances')->with($user, [$token]);

        return $balanceHandler;
    }

    private function mockBalanceView(Money $money): BalanceView
    {
        $balanceView = $this->createMock(BalanceView::class);
        $balanceView->method('getAvailable')->willReturn($money);

        return $balanceView;
    }

    private function mockBalanceViewFactory(string $key, BalanceView $balanceView): BalanceViewFactoryInterface
    {
        $balanceViewFactory = $this->createMock(BalanceViewFactoryInterface::class);
        $balanceViewFactory->method('create')->willReturn([$key => $balanceView]);

        return $balanceViewFactory;
    }

    private function mockLogger(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
    }

    private function mockParameterBag(): ParameterBagInterface
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturnMap([
            ['maker_fee_rate', 1],
            ['taker_fee_rate', 2],
            ['token_precision', 3],
        ]);

        return $bag;
    }

    private function mockMarketHandler(array $buyOrders, array $sellOrders): MarketHandlerInterface
    {
        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler->method('getPendingBuyOrders')->willReturn($buyOrders);
        $marketHandler->method('getPendingSellOrders')->willReturn($sellOrders);

        return $marketHandler;
    }

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('findByName')->willReturn($token);

        return $tokenManager;
    }

    private function mockOrder(int $price): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getPrice')->willReturn(
            $this->money($price)
        );

        return $order;
    }

    private function mockToken(string $symbol, User $user): Token
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user);

        $tok = $this->createMock(Token::class);
        $tok->method('getSymbol')->willReturn($symbol);
        $tok->method('getProfile')->willReturn($profile);

        return $tok;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $tok = $this->createMock(Crypto::class);
        $tok->method('getSymbol')->willReturn($symbol);
        $tok->method('getShowSubunit')->willReturn(4);

        return $tok;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockMarket(TradebleInterface $base, TradebleInterface $quote, bool $isTokMarket): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getBase')->willReturn($base);
        $market->method('getQuote')->willReturn($quote);
        $market->method('isTokenMarket')->willReturn($isTokMarket);

        return $market;
    }

    private function mockTradeResult(): TradeResult
    {
        return $this->createMock(TradeResult::class);
    }
}
