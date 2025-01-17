<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\PlaceOrderResult;
use App\Exchange\Trade\Trader;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderFetcherInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\TokenInitOrderRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TraderTest extends TestCase
{
    public function testPlaceOrderForToken(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('placeOrder')
            ->with(1, 'BARFOO', 1, 100, 50, '0.02', '0.02', 2, 0.5)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = $this->mockTrader(
            $trader,
            null,
            $this->mockEm($this->never()),
        );

        $quote = $this->mockToken('BAR', true);

        $user = $this->mockUser(1);
        $user->method('getReferencer')->willReturn(null);

        $trader->placeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ))
        );
    }

    public function testPlaceOrderForTokenAndForUserWithReferencer(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('placeOrder')
            ->with(2, 'BARFOO', 1, 100, 50, '0.02', '0.02', 2, 0.5)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = $this->mockTrader(
            $trader,
            null,
            $this->mockEm($this->never()),
        );

        $quote = $this->mockToken('BAR', true);

        $referrencer = $this->mockUser(1);
        $user = $this->mockUser(2);
        $user->method('getReferencer')->willReturn($referrencer);


        $trader->placeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ))
        );
    }

    public function testPlaceOrderForCrypto(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('placeOrder')
            ->with(1, 'BARFOO', 1, 100, 50, '0.02', '0.02', 2, 0.5)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = $this->mockTrader(
            $trader,
            null,
            $this->mockEm($this->once()),
        );

        $quote = $this->mockToken('BAR', false);

        $user = $this->mockUser(1);
        $user->expects($this->once())->method('addCrypto');

        $trader->placeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ))
        );
    }

    public function testPlaceOrderFailed(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('placeOrder')
            ->with(1, 'BARFOO', 1, 100, 50, '0.02', '0.02', 2, 0.5)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::FAILED
                )
            );

        $trader = $this->mockTrader(
            $trader
        );

        $quote = $this->mockToken('BAR', true);

        $user = $this->mockUser(1);
        $user->expects($this->never())->method('addCrypto');

        $trader->placeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ))
        );
    }

    public function testExecuteOrder(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('executeOrder')
            ->with(1, 'BARFOO', 1, 100, '1', 2, 0.5)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = $this->mockTrader(
            $trader,
            null,
            $this->mockEm($this->never()),
        );

        $quote = $this->mockToken('BAR', true);

        $user = $this->mockUser(1);

        $trader->executeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ), 1)
        );
    }

    public function testCancelOrder(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('cancelOrder')
            ->with(1, 'BARFOO', 999)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = $this->mockTrader(
            $trader
        );

        $trader->cancelOrder(
            $this->mockOrder($this->mockUser(1), 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $this->mockToken('BAR', true)
            ))
        );
    }

    public function testCancelOrderFailed(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('cancelOrder')
            ->with(1, 'BARFOO', 999)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::FAILED
                )
            );

        $trader = $this->mockTrader(
            $trader
        );

        $trader->cancelOrder(
            $this->mockOrder($this->mockUser(1), 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $this->mockToken('BAR', true)
            ))
        );
    }

    public function testGetFinishedOrders(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('getFinishedOrders')
            ->with(2, 'BARFOO', 0)
            ->willReturn(
                [[
                    'id' => 1,
                    'amount' => '2',
                    'side' => 1,
                    'price' => '3',
                    'mtime' => 4,
                ]]
            );

        $trader = $this->mockTrader(
            $trader
        );

        $user = $this->mockUser(2);
        $market = $this->mockMarket(
            $this->mockToken('FOO', false),
            $this->mockToken('BAR', false)
        );
        $rows = $trader->getFinishedOrders(
            $user,
            $market
        );

        $this->assertEquals([
            [1, $market, 1, 0, '2', '3', 'finished', $user],
        ], array_map(function (Order $order): array {
            return [
                $order->getId(),
                $order->getMarket(),
                $order->getSide(),
                $order->getReferralId(),
                $order->getAmount()->getAmount(),
                $order->getPrice()->getAmount(),
                $order->getStatus(),
                $order->getMaker(),
            ];
        }, $rows));
    }

    public function testGetPendingOrders(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('getPendingOrders')
            ->with(2, 'BARFOO', 0)
            ->willReturn(
                [[
                    'id' => 1,
                    'amount' => '2',
                    'side' => 1,
                    'price' => '3',
                    'mtime' => 4,
                ]]
            );

        $trader = $this->mockTrader(
            $trader
        );

        $user = $this->mockUser(2);
        $market = $this->mockMarket(
            $this->mockToken('FOO', false),
            $this->mockToken('BAR', true)
        );
        $rows = $trader->getPendingOrders(
            $user,
            $market
        );

        $this->assertEquals([
            [1, $market, 1, 0, '2', '3', 'pending', $user],
        ], array_map(function (Order $order): array {
            return [
                $order->getId(),
                $order->getMarket(),
                $order->getSide(),
                $order->getReferralId(),
                $order->getAmount()->getAmount(),
                $order->getPrice()->getAmount(),
                $order->getStatus(),
                $order->getMaker(),
            ];
        }, $rows));
    }

    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        $converter = $this->createMock(MarketNameConverterInterface::class);
        $converter->method('convert')->willReturnCallback(function (Market $market): string {
            return $market->getQuote()->getSymbol() . $market->getBase()->getSymbol();
        });

        return $converter;
    }

    private function mockLimitOrderConfig(
        string $feeTokenRate,
        string $feeCryptoRate
    ): LimitOrderConfig {
        $config = $this->createMock(LimitOrderConfig::class);
        $config
            ->method('getFeeTokenRate')
            ->willReturn($feeTokenRate);
        $config
            ->method('getFeeCryptoRate')
            ->willReturn($feeCryptoRate);

        return $config;
    }

    private function mockEm(InvokedCount $invocation): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($invocation)->method('flush');

        return $em;
    }

    /** @return TraderFetcherInterface|MockObject */
    private function mockTraderFetcher(): TraderFetcherInterface
    {
        return $this->createMock(TraderFetcherInterface::class);
    }

    private function mockTradeResult(int $resultCode): PlaceOrderResult
    {
        $result = $this->createMock(PlaceOrderResult::class);
        $result->method('getResult')->willReturn($resultCode);
        $result->method('getLeft')->willReturn("1");
        $result->method('getAmount')->willReturn("1");

        return $result;
    }

    private function mockOrder(
        User $user,
        int $side,
        int $amount,
        int $price,
        int $referralId,
        Market $market,
        int $fee = 1
    ): Order {
        $order = $this->createMock(Order::class);
        $order->method('getMaker')->willReturn($user);
        $order->method('getSide')->willReturn($side);
        $order->method('getAmount')->willReturn(Money::USD($amount));
        $order->method('getPrice')->willReturn(Money::USD($price));
        $order->method('getFee')->willReturn(Money::USD($fee));
        $order->method('getReferralId')->willReturn($referralId);
        $order->method('getMarket')->willReturn($market);
        $order->method('getId')->willReturn(999);

        return $order;
    }

    /** @return TradableInterface|MockObject */
    private function mockToken(string $symbol, bool $isTok): TradableInterface
    {
        /** @var TradableInterface|MockObject $tok */
        $tok = $this->createMock($isTok ? Token::class : Crypto::class);
        $tok->method('getSymbol')->willReturn($symbol);

        return $tok;
    }

    private function mockMarket(TradableInterface $base, TradableInterface $quote): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getBase')->willReturn($base);
        $market->method('getQuote')->willReturn($quote);

        return $market;
    }

    /** @return User|MockObject */
    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn($id);
        $user
            ->method('getTradingFee')
            ->willReturn('0.02');

        return $user;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });
        $mw->method('format')->willReturnCallback(function (Money $money): string {
            return $money->getAmount();
        });

        return $mw;
    }

    private function mockEvenDispatcher(): EventDispatcherInterface
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    private function mockTrader(
        ?TraderFetcherInterface $fetcher = null,
        ?LimitOrderConfig $config = null,
        ?EntityManagerInterface $entityManager = null,
        ?MoneyWrapperInterface $moneyWrapper = null,
        ?MarketNameConverterInterface $marketNameConverter = null,
        ?NormalizerInterface $normalizer = null,
        ?LoggerInterface $logger = null,
        ?float $referralFee = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?BalanceHandlerInterface $balanceHandler = null
    ): Trader {
        return new Trader(
            $fetcher ?? $this->mockTraderFetcher(),
            $config ?? $this->mockLimitOrderConfig('0.02', '0.02'),
            $entityManager ?? $this->mockEm($this->never()),
            $moneyWrapper ?? $this->mockMoneyWrapper(),
            $marketNameConverter ?? $this->mockMarketNameConverter(),
            $normalizer ?? $this->createMock(NormalizerInterface::class),
            $logger ?? $this->createMock(LoggerInterface::class),
            $referralFee ?? 0.5,
            $eventDispatcher ?? $this->mockEvenDispatcher(),
            $balanceHandler ?? $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(MarketHandlerInterface::class),
            $this->createMock(UserManagerInterface::class),
            $this->createMock(TokenInitOrderRepository::class)
        );
    }
}
