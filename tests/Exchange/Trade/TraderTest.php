<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Exchange\Trade\Trader;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderFetcherInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TraderTest extends TestCase
{
    public function testPlaceOrderForToken(): void
    {
        $trader = $this->mockTraderFetcher();
        $trader->expects($this->once())
            ->method('placeOrder')
            ->with(1, 'BARFOO', 1, 100, 50, 2, 1, 2, 1)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->exactly(2)),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $quote = $this->mockToken('BAR', true);

        $user = $this->mockUser(1);
        $user->method('getReferrencer')->willReturn($user);
        $user->expects($this->exactly(2))->method('addToken');

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
            ->with(1, 'BARFOO', 1, 100, 50, 2, 1, 2, 1)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::SUCCESS
                )
            );

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->once()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
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
            ->with(1, 'BARFOO', 1, 100, 50, 2, 1, 2, 1)
            ->willReturn(
                $this->mockTradeResult(
                    TradeResult::FAILED
                )
            );

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->never()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $quote = $this->mockToken('BAR', true);

        $user = $this->mockUser(1);
        $user->expects($this->never())->method('addToken');
        $user->expects($this->never())->method('addCrypto');

        $trader->placeOrder(
            $this->mockOrder($user, 1, 100, 50, 2, $this->mockMarket(
                $this->mockToken('FOO', false),
                $quote
            ))
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

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->never()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
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

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->never()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
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

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->never()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
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

        $trader = new Trader(
            $trader,
            $this->mockLimitOrderConfig(1, 2),
            $this->mockEm($this->never()),
            $this->mockMoneyWrapper(),
            $this->mockPrelaunchConfig(1, false),
            $this->mockMarketNameConverter(),
            $this->createMock(NormalizerInterface::class),
            $this->createMock(LoggerInterface::class)
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

    private function mockLimitOrderConfig(int $makerFee, int $takerFee): LimitOrderConfig
    {
        $config = $this->createMock(LimitOrderConfig::class);
        $config->method('getMakerFeeRate')->willReturn($makerFee);
        $config->method('getTakerFeeRate')->willReturn($takerFee);

        return $config;
    }

    private function mockEm(Invocation $invocation): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($invocation)->method('flush');

        return $em;
    }

    private function mockPrelaunchConfig(int $refFee, bool $enabled): PrelaunchConfig
    {
        $config = $this->createMock(PrelaunchConfig::class);
        $config->method('getReferralFee')->willReturn($refFee);
        $config->method('isEnabled')->willReturn($enabled);

        return $config;
    }

    /** @return TraderFetcherInterface|MockObject */
    private function mockTraderFetcher(): TraderFetcherInterface
    {
        return $this->createMock(TraderFetcherInterface::class);
    }

    private function mockTradeResult(int $resultCode): TradeResult
    {
        $result = $this->createMock(TradeResult::class);
        $result->method('getResult')->willReturn($resultCode);

        return $result;
    }

    private function mockOrder(User $user, int $side, int $amount, int $price, int $referralId, Market $market): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getMaker')->willReturn($user);
        $order->method('getSide')->willReturn($side);
        $order->method('getAmount')->willReturn(Money::USD($amount));
        $order->method('getPrice')->willReturn(Money::USD($price));
        $order->method('getReferralId')->willReturn($referralId);
        $order->method('getMarket')->willReturn($market);
        $order->method('getId')->willReturn(999);

        return $order;
    }

    /** @return TradebleInterface|MockObject */
    private function mockToken(string $symbol, bool $isTok): TradebleInterface
    {
        /** @var TradebleInterface|MockObject $tok */
        $tok = $this->createMock($isTok ? Token::class : Crypto::class);
        $tok->method('getSymbol')->willReturn($symbol);

        return $tok;
    }

    private function mockMarket(TradebleInterface $base, TradebleInterface $quote): Market
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
        $user->method('getId')->willReturn($id);

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
}
