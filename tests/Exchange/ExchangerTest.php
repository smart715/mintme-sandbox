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
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Exchanger;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\TokenManagerInterface;
use App\Tests\MockMoneyWrapper;
use App\Utils\Symbols;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExchangerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testPlaceOrderInsufficientBalance(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->never()),
            $this->mockBalanceHandler($this->once(), $user, $tok, null),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(1))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok, null),
            $this->mockValidator(true),
            $this->mockTranslator()
        );
        $result = $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true,
            ),
            '4',
            '5',
            false,
            Order::SELL_SIDE,
        );

        self::assertEquals(TradeResult::INSUFFICIENT_BALANCE, $result->getResult());
    }

    public function testPlaceOrderSmallAmount(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->never()),
            $this->mockBalanceHandler($this->once(), $user, $tok, null),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok, null),
            $this->mockValidator(false),
            $this->mockTranslator()
        );
        $result = $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true
            ),
            '4',
            '5',
            false,
            Order::SELL_SIDE,
        );

        self::assertEquals(
            TradeResult::SMALL_AMOUNT,
            $result->getResult(),
            $result->getMessage()
        );
    }

    public function testPlaceOrderSuccess(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $exchanger = new Exchanger(
            $this->mockTrader($tradeResult),
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->once()),
            $this->mockBalanceHandler($this->once(), $user, $tok, null),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandler([$this->mockOrder(2)], []),
            $this->mockTokenManager($tok, null),
            $this->mockValidator(true),
            $this->mockTranslator()
        );
        $result = $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true
            ),
            '4',
            '5',
            false,
            Order::SELL_SIDE,
        );

        $this->assertEquals($tradeResult, $result);
    }

    public function testPlaceOrderSuccessMarketPrice(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $trader = $this->mockTrader($tradeResult);
        $trader->expects($this->once())->method('placeOrder')->with(
            $this->callback(fn (Order $o) => '1' === $o->getPrice()->getAmount())
        );

        $exchanger = new Exchanger(
            $trader,
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->once()),
            $this->mockBalanceHandler($this->once(), $user, $tok, $this->mockBalanceResult()),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandlerForConsecutiveCalls(),
            $this->mockTokenManager($tok, $this->mockBalanceResult()),
            $this->mockValidator(true),
            $this->mockTranslator()
        );
        $result = $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true
            ),
            '4',
            '5',
            true,
            Order::SELL_SIDE,
        );

        $this->assertEquals($tradeResult, $result);
    }

    public function testPlaceOrderOnValidatorFailed(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $trader = $this->mockTrader($tradeResult);

        $exchanger = new Exchanger(
            $trader,
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->never()),
            $this->mockBalanceHandler($this->once(), $user, $tok, $this->mockBalanceResult()),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandlerForConsecutiveCalls(),
            $this->mockTokenManager($tok, $this->mockBalanceResult()),
            $this->mockValidator(false),
            $this->mockTranslator()
        );

        $trader->expects($this->never())->method('placeOrder');

        $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true
            ),
            '50',
            '50',
            true,
            Order::SELL_SIDE,
        );
    }

    public function testPlaceOlderOnValidatorSuccess(): void
    {
        $user = $this->mockUser();
        $tok = $this->mockToken(Symbols::TOK, $user);
        $tradeResult = $this->mockTradeResult();
        $trader = $this->mockTrader($tradeResult);
        $trader->expects($this->once())->method('placeOrder')->with(
            $this->callback(fn (Order $o) => '1' === $o->getPrice()->getAmount())
        );

        $exchanger = new Exchanger(
            $trader,
            $this->mockMoneyWrapper(),
            $this->mockMarketProducer($this->once()),
            $this->mockBalanceHandler($this->once(), $user, $tok, $this->mockBalanceResult()),
            $this->mockBalanceViewFactory($tok->getSymbol(), $this->mockBalanceView($this->money(100))),
            $this->mockLogger(),
            $this->mockParameterBag(),
            $this->mockMarketHandlerForConsecutiveCalls(),
            $this->mockTokenManager($tok, $this->mockBalanceResult()),
            $this->mockValidator(true),
            $this->mockTranslator()
        );
        $result = $exchanger->placeOrder(
            $user,
            $this->mockMarket(
                $this->mockCrypto('WEB'),
                $tok,
                true
            ),
            '0',
            '0.1',
            true,
            Order::SELL_SIDE,
        );
        self::assertEquals(null, $result->getResult());
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
        return new Money($amount, new Currency(Symbols::TOK));
    }

    /**
     * @return TraderInterface|MockObject
     */
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

    /**
     * @return BalanceHandlerInterface|MockObject
     */
    private function mockBalanceHandler(
        InvokedCount $count,
        User $user,
        Token $token,
        ?BalanceResult $br
    ): BalanceHandlerInterface {
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->expects($count)->method('balances')->with($user, [$token]);

        if ($br) {
            $balanceHandler->method('balance')->with($user, $token)->willReturn($br);
        }

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
        $marketHandler->method('getPendingBuyOrders')->willReturnOnConsecutiveCalls([
            $this->mockOrder(3, 1),
            $this->mockOrder(2, 1),
            $this->mockOrder(1, 1),
        ], []);

        return $marketHandler;
    }

    /**
     * @return TokenManagerInterface|MockObject
     */
    private function mockTokenManager(?Token $token, ?BalanceResult $br): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('findByName')->willReturn($token);

        if ($br) {
            $tokenManager->method('getRealBalance')->with($token, $br)->willReturn($br);
        }

        return $tokenManager;
    }

    private function mockOrder(int $price, ?int $amount = null): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getPrice')->willReturn(
            $this->money($price)
        );

        if (null !== $amount) {
            $order->method('getAmount')->willReturn(
                $this->money($amount)
            );
        }

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

    private function mockTranslator(): TranslatorInterface
    {
        return $this->createMock(TranslatorInterface::class);
    }

    private function mockBalanceResult(): BalanceResult
    {
        $balance = $this->money(6);
        $br = $this->createMock(BalanceResult::class);
        $br->method('getAvailable')->willReturn($balance);

        return $br;
    }

    private function mockMarketHandlerForConsecutiveCalls(): MarketHandlerInterface
    {
        $mh = $this->createMock(MarketHandlerInterface::class);
        $mh->method('getPendingBuyOrders')->willReturnOnConsecutiveCalls([
            $this->mockOrder(3, 1),
            $this->mockOrder(2, 1),
            $this->mockOrder(1, 1),
        ], []);

        return $mh;
    }
}
