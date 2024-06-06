<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenInitOrder;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\OrdersFactory;
use App\Exchange\Market;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OrdersFactoryTest extends TestCase
{
    private const TOTAL_ORDERS = 5;
    private const MIN_TOKENS_AMOUNT = 1;

    use MockMoneyWrapper;

    public function testCreateInitOrders(): void
    {
        $tradeResult = $this->createMock(TradeResult::class);
        $tradeResult->method('getResult')->willReturn(1);

        $trader = $this->createMock(TraderInterface::class);
        $trader->expects($this->exactly(5))->method('placeOrder')->willReturn($tradeResult);

        $factory = new OrdersFactory(
            $trader,
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockParameterBag(),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $factory->createInitOrders(
            $this->mockToken(),
            "10",
            "10",
            "10"
        );
    }

    public function testCreateInitOrdersWithNullPriceGrowth(): void
    {
        $trader = $this->createMock(TraderInterface::class);
        $trader->expects($this->never())->method('placeOrder');

        $factory = new OrdersFactory(
            $trader,
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockParameterBag(),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $factory->createInitOrders(
            $this->mockToken(),
            "10",
            null,
            "10"
        );
    }

    public function testCreateInitOrdersWithException(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturn(['totalOrders' => 0, 'minTokensAmount' => self::MIN_TOKENS_AMOUNT]);

        $factory = new OrdersFactory(
            $this->createMock(TraderInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $parameterBag,
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $this->expectException(\LogicException::class);

        $factory->createInitOrders(
            $this->mockToken(false),
            'TEST',
            'TEST',
            'TEST'
        );
    }

    public function testCreateInitOrdersWithWrongMinTokensAmount(): void
    {
        $factory = new OrdersFactory(
            $this->createMock(TraderInterface::class),
            $this->createMock(MarketFactoryInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->mockParameterBag(),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $this->expectException(ApiBadRequestException::class);

        $factory->createInitOrders(
            $this->mockToken(),
            "0.1",
            "10",
            "10"
        );
    }

    public function testRemoveTokenInitOrders(): void
    {
        $tradeResult = $this->createMock(TradeResult::class);
        $tradeResult->method('getResult')->willReturn(0);

        $trader = $this->createMock(TraderInterface::class);
        $trader->expects($this->once())->method('cancelOrder')->willReturn($tradeResult);

        $factory = new OrdersFactory(
            $trader,
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->createMock(ParameterBagInterface::class),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $factory->removeTokenInitOrders(
            $this->mockUser(),
            $this->mockToken(),
            $this->mockTokenInitOrder()
        );
    }

    public function testRemoveTokenWithOrderNotFoundResult(): void
    {
        $tradeResult = $this->createMock(TradeResult::class);
        $tradeResult->method('getResult')->willReturn(4);

        $trader = $this->createMock(TraderInterface::class);
        $trader->expects($this->once())->method('cancelOrder')->willReturn($tradeResult);

        $factory = new OrdersFactory(
            $trader,
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->createMock(ParameterBagInterface::class),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $this->expectException(ApiBadRequestException::class);

        $factory->removeTokenInitOrders(
            $this->mockUser(),
            $this->mockToken(),
            $this->mockTokenInitOrder()
        );
    }

    public function testRemoveTokenWithUserNotFoundResult(): void
    {
        $tradeResult = $this->createMock(TradeResult::class);
        $tradeResult->method('getResult')->willReturn(5);

        $trader = $this->createMock(TraderInterface::class);
        $trader->expects($this->once())->method('cancelOrder')->willReturn($tradeResult);

        $factory = new OrdersFactory(
            $trader,
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->createMock(ParameterBagInterface::class),
            $this->mockMoneyWrapper(),
            $this->mockLimitOrderConfig(),
            $this->mockBalanceHandler(),
            $this->mockBalanceViewerFactory()
        );

        $this->expectException(ApiBadRequestException::class);

        $factory->removeTokenInitOrders(
            $this->mockUser(),
            $this->mockToken(),
            $this->mockTokenInitOrder()
        );
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory->method('create')->willReturn($this->mockMarket());

        return $marketFactory;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->expects($this->once())->method('findBySymbol')->willReturn($this->mockCrypto());

        return $cryptoManager;
    }

    private function mockParameterBag(): ParameterBagInterface
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->expects($this->once())
            ->method('get')
            ->willReturn(['totalOrders' => self::TOTAL_ORDERS, 'minTokensAmount' => self::MIN_TOKENS_AMOUNT]);

        return $parameterBag;
    }

    private function mockToken(bool $haveUser = true): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getOwner')->willReturn($haveUser ? $this->mockUser() : null);
        $token->method('getSymbol')->willReturn('TOK');

        return $token;
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getTradingFee')->willReturn('1');

        return $user;
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockMarket(): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getQuote')->willReturn($this->mockQuote());

        return $market;
    }

    private function mockQuote(): TradableInterface
    {
        $quote = $this->createMock(TradableInterface::class);
        $quote->method('getSymbol')->willReturn('TOK');

        return $quote;
    }

    public function mockTokenInitOrder(): TokenInitOrder
    {
        $tokenInitOrder = $this->createMock(TokenInitOrder::class);
        $tokenInitOrder->method('getOrderId')->willReturn(1);

        return $tokenInitOrder;
    }

    private function mockLimitOrderConfig(): LimitOrderConfig
    {
        $config = $this->createMock(LimitOrderConfig::class);
        $config
            ->method('getFeeTokenRate')
            ->willReturn('0.02');

        return $config;
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    private function mockBalanceViewerFactory(): BalanceViewFactoryInterface
    {
        $factory = $this->createMock(BalanceViewFactoryInterface::class);
        $factory->method('create')->willReturn(['TOK' => $this->mockBalanceView()]);

        return $factory;
    }

    public function mockBalanceView(): BalanceView
    {
        $balanceView = $this->createMock(BalanceView::class);
        $balanceView->method('getAvailable')->willReturn(new Money('100', new Currency('TOK')));

        return $balanceView;
    }
}
