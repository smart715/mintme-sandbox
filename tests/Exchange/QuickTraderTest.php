<?php declare(strict_types = 1);

namespace App\Tests\Exchange;

use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\QuickTradeException;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\ExchangerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Market\Model\BuyOrdersSummaryResult;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Exchange\QuickTrader;
use App\Exchange\Trade\CheckTradeResult;
use App\Exchange\Trade\TradeResult;
use App\Logger\UserActionLogger;
use App\Tests\Mocks\MockMoneyWrapperWithDecimal;
use App\Utils\Validator\ValidatorInterface;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use PHPUnit\Framework\TestCase;

class QuickTraderTest extends TestCase
{

    use MockMoneyWrapperWithDecimal;

    public function testMakeSellSuccess(): void
    {
        $tradeResult = $this->mockTradeResult();

        $quickTrader = new QuickTrader(
            $this->mockExchanger($tradeResult),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(),
            $this->mockValidatorFactory(),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $tradeResult,
            $quickTrader->makeSell($this->mockUser(), $this->mockMarket(), '1', '1')
        );
    }

    public function testMakeSellWithLessThanMinimumAmountWillRaiseException(): void
    {
        $quickTrader = new QuickTrader(
            $this->mockExchanger(),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(false),
            $this->mockValidatorFactory(false),
            $this->mockUserActionLogger(),
        );

        $this->expectException(QuickTradeException::class);

        $quickTrader->makeSell($this->mockUser(), $this->mockMarket(false), '1', '1');
    }

    public function testMakeSellWithFalseExpectAmountWillRaiseException(): void
    {
        $quickTrader = new QuickTrader(
            $this->mockExchanger(),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(true, '2'),
            $this->mockValidatorFactory(),
            $this->mockUserActionLogger()
        );

        $this->expectException(QuickTradeException::class);

        $quickTrader->makeSell($this->mockUser(), $this->mockMarket(), '1', '1');
    }

    public function testMakeBuySuccess(): void
    {
        $tradeResult = $this->mockTradeResult();

        $quickTrader = new QuickTrader(
            $this->mockExchanger($tradeResult),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(true, '1', false),
            $this->mockValidatorFactory(),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $tradeResult,
            $quickTrader->makeBuy($this->mockUser(), $this->mockMarket(), '1', '1')
        );
    }

    public function testMakeBuyWithLessThanMinimumAmountWillRaiseException(): void
    {
        $quickTrader = new QuickTrader(
            $this->mockExchanger(),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(false),
            $this->mockValidatorFactory(false),
            $this->mockUserActionLogger(),
        );

        $this->expectException(QuickTradeException::class);

        $quickTrader->makeBuy($this->mockUser(), $this->mockMarket(false), '1', '1');
    }

    public function testMakeBuyWithFalseExpectAmountWillRaiseException(): void
    {
        $quickTrader = new QuickTrader(
            $this->mockExchanger(),
            $this->mockQuickTradeConfig(),
            $this->mockMoneyWrapperWithDecimal(),
            $this->mockMarketHandler(true, '2', false),
            $this->mockValidatorFactory(),
            $this->mockUserActionLogger(),
        );

        $this->expectException(QuickTradeException::class);

        $quickTrader->makeBuy($this->mockUser(), $this->mockMarket(), '1', '1');
    }

    public function testCheckSell(): void
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->expects($this->once())
            ->method('getExpectedSellResult')
            ->willReturn($checkTradeResult);

        $quickTrader = new QuickTrader(
            $this->createMock(ExchangerInterface::class),
            $this->createMock(QuickTradeConfig::class),
            $this->createMock(MoneyWrapperInterface::class),
            $marketHandler,
            $this->createMock(ValidatorFactoryInterface::class),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $checkTradeResult,
            $quickTrader->checkSell($this->createMock(Market::class), '1')
        );
    }

    public function testCheckSellReversed(): void
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->expects($this->once())
            ->method('getExpectedSellReversedResult')
            ->willReturn($checkTradeResult);

        $quickTrader = new QuickTrader(
            $this->createMock(ExchangerInterface::class),
            $this->createMock(QuickTradeConfig::class),
            $this->createMock(MoneyWrapperInterface::class),
            $marketHandler,
            $this->createMock(ValidatorFactoryInterface::class),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $checkTradeResult,
            $quickTrader->checkSellReversed($this->createMock(Market::class), '1')
        );
    }

    public function testCheckBuy(): void
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->expects($this->once())
            ->method('getExpectedBuyResult')
            ->willReturn($checkTradeResult);

        $quickTrader = new QuickTrader(
            $this->createMock(ExchangerInterface::class),
            $this->createMock(QuickTradeConfig::class),
            $this->createMock(MoneyWrapperInterface::class),
            $marketHandler,
            $this->createMock(ValidatorFactoryInterface::class),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $checkTradeResult,
            $quickTrader->checkBuy($this->createMock(Market::class), '1')
        );
    }

    public function testCheckBuyReversed(): void
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->expects($this->once())
            ->method('getExpectedBuyReversedResult')
            ->willReturn($checkTradeResult);

        $quickTrader = new QuickTrader(
            $this->createMock(ExchangerInterface::class),
            $this->createMock(QuickTradeConfig::class),
            $this->createMock(MoneyWrapperInterface::class),
            $marketHandler,
            $this->createMock(ValidatorFactoryInterface::class),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $checkTradeResult,
            $quickTrader->checkBuyReversed($this->createMock(Market::class), '1')
        );
    }

    public function testCheckDonationReversed(): void
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);

        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler
            ->expects($this->once())
            ->method('getExpectedDonationReversedResult')
            ->willReturn($checkTradeResult);

        $quickTrader = new QuickTrader(
            $this->createMock(ExchangerInterface::class),
            $this->createMock(QuickTradeConfig::class),
            $this->createMock(MoneyWrapperInterface::class),
            $marketHandler,
            $this->createMock(ValidatorFactoryInterface::class),
            $this->mockUserActionLogger()
        );

        $this->assertEquals(
            $checkTradeResult,
            $quickTrader->checkDonationReversed($this->createMock(Market::class), '1')
        );
    }

    private function mockExchanger(?TradeResult $tradeResult = null): ExchangerInterface
    {
        $exchanger = $this->createMock(ExchangerInterface::class);

        $exchanger->expects($tradeResult ? $this->once() : $this->never())
            ->method('executeOrder')
            ->willReturn($tradeResult ?? $this->mockTradeResult());

        return $exchanger;
    }

    private function mockQuickTradeConfig(): QuickTradeConfig
    {
        $quickTradeConfig = $this->createMock(QuickTradeConfig::class);

        $quickTradeConfig->expects($this->once())
            ->method('getMinAmountBySymbol')
            ->willReturn($this->dummyMoneyObject());

        return $quickTradeConfig;
    }

    private function mockMarketHandler(
        bool $isValid = true,
        string $amount = '1',
        bool $isSell = true
    ): MarketHandlerInterface {
        $marketHandler = $this->createMock(MarketHandlerInterface::class);

        $marketHandler->expects($isValid && $isSell ? $this->once() : $this->never())
            ->method('getExpectedSellResult')
            ->willReturn($this->mockCheckTradeResult($amount));

        $marketHandler->expects($isValid && $isSell ? $this->once() : $this->never())
            ->method('getBuyOrdersSummary')
            ->willReturn($this->mockBuyOrdersSummaryResult());

        $marketHandler->expects($isValid && !$isSell ? $this->once() : $this->never())
            ->method('getExpectedBuyResult')
            ->willReturn($this->mockCheckTradeResult($amount));

        $marketHandler->expects($isValid && !$isSell ? $this->once() : $this->never())
            ->method('getSellOrdersSummary')
            ->willReturn($this->mockSellOrdersSummaryResult());

        return $marketHandler;
    }

    private function mockValidatorFactory(bool $isValid = true): ValidatorFactoryInterface
    {
        $validatorFactory = $this->createMock(ValidatorFactoryInterface::class);
        $validatorFactory->expects($this->once())
            ->method('createMinTradableValidator')
            ->willReturn($this->mockValidatorInterface($isValid));

        $validatorFactory->expects($this->once())
            ->method('createMinUsdValidator')
            ->willReturn($this->mockValidatorInterface($isValid));

        return $validatorFactory;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockMarket(bool $isValid = true): Market
    {
        $market = $this->createMock(Market::class);

        $market->expects($isValid ? $this->exactly(2) : $this->once())
            ->method('getBase')
            ->willReturn($this->mockTradable());

        $market->expects($isValid ? $this->once() : $this->never())
            ->method('getQuote')
            ->willReturn($this->mockTradable());

        return $market;
    }

    private function mockTradable(): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getMoneySymbol')->willReturn('BASE');
        $tradable->method('getSymbol')->willReturn('TOK');

        return $tradable;
    }

    private function dummyMoneyObject(string $amount = '1', string $symbol = 'TOK'): Money
    {
        return $this->mockMoneyWrapperWithDecimal()->parse($amount, $symbol);
    }

    private function mockValidatorInterface(bool $isValid): ValidatorInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($isValid);

        return $validator;
    }

    private function mockCheckTradeResult(string $amount): CheckTradeResult
    {
        $checkTradeResult = $this->createMock(CheckTradeResult::class);
        $checkTradeResult->method('getExpectedAmount')->willReturn($this->dummyMoneyObject($amount));
        $checkTradeResult->method('getWorth')->willReturn($this->dummyMoneyObject($amount));

        return $checkTradeResult;
    }

    private function mockBuyOrdersSummaryResult(): BuyOrdersSummaryResult
    {
        $buyOrdersSummaryResult = $this->createMock(BuyOrdersSummaryResult::class);
        $buyOrdersSummaryResult->method('getQuoteAmount')->willReturn('1');

        return $buyOrdersSummaryResult;
    }

    private function mockTradeResult(): TradeResult
    {
        return $this->createMock(TradeResult::class);
    }

    private function mockSellOrdersSummaryResult(): SellOrdersSummaryResult
    {
        $sellOrdersSummaryResult = $this->createMock(SellOrdersSummaryResult::class);
        $sellOrdersSummaryResult->method('getBaseAmount')->willReturn('1');

        return $sellOrdersSummaryResult;
    }

    private function mockUserActionLogger(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
    }
}
