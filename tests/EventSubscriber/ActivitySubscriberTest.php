<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Activity\Activity;
use App\Entity\Activity\AirdropClaimedActivity;
use App\Entity\Activity\AirdropCreatedActivity;
use App\Entity\Activity\AirdropEndedActivity;
use App\Entity\Activity\DonationActivity;
use App\Entity\Activity\NewPostActivity;
use App\Entity\Activity\TokenCreatedActivity;
use App\Entity\Activity\TokenDeployedActivity;
use App\Entity\Activity\TokenDepositedActivity;
use App\Entity\Activity\TokenTradedActivity;
use App\Entity\Activity\TokenWithdrawnActivity;
use App\Entity\Activity\UserAmountActivity;
use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Crypto;
use App\Entity\Donation;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DonationEvent;
use App\Events\OrderEventInterface;
use App\Events\TokenEventInterface;
use App\Events\TransactionCompletedEvent;
use App\Events\UserAirdropEvent;
use App\EventSubscriber\ActivitySubscriber;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Mercure\PublisherInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActivitySubscriberTest extends TestCase
{
    /** @dataProvider handleTokenEventDataProvider */
    public function testHandleTokenEvent(string $eventName, string $activityClass): void
    {
        $token = $this->createMock(Token::class);
        $event = $this->createMock(TokenEventInterface::class);
        $event->method('getToken')->willReturn($token);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn (Activity $a) => $a instanceof $activityClass && $a->getToken() === $token));

        $subscriber = new ActivitySubscriber(
            $em,
            $this->createMock(MoneyWrapperInterface::class),
            $this->createMock(CryptoRatesFetcherInterface::class),
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->handleTokenEvent($event, $eventName);
    }

    public function handleTokenEventDataProvider(): array
    {
        return [
            ['token.created', TokenCreatedActivity::class],
            ['token.deployed', TokenDeployedActivity::class],
            ['airdrop.created', AirdropCreatedActivity::class],
            ['airdrop.ended', AirdropEndedActivity::class],
            ['post.created', NewPostActivity::class],
        ];
    }

    public function testAirdropClaimed(): void
    {
        $reward = new Money('1', new Currency('FOO'));

        $airdrop = $this->createMock(Airdrop::class);
        $airdrop->method('getReward')->willReturn($reward);

        $token = $this->createMock(Token::class);

        $user = $this->createMock(User::class);

        $event = $this->createMock(UserAirdropEvent::class);
        $event->method('getAirdrop')->willReturn($airdrop);
        $event->method('getToken')->willReturn($token);
        $event->method('getUser')->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                function (Activity $a) use ($token, $user, $reward) {
                    return $a instanceof AirdropClaimedActivity
                        && $a->getToken() === $token
                        && $a->getUser() === $user
                        && $a->getAmount()->getAmount() === $reward->getAmount()
                        && 'TOK' === $a->getAmount()->getCurrency()->getCode();
                }
            ));

        $subscriber = new ActivitySubscriber(
            $em,
            $this->createMock(MoneyWrapperInterface::class),
            $this->createMock(CryptoRatesFetcherInterface::class),
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->airdropClaimed($event, 'airdrop.claimed');
    }

    public function testHandleTransactionEventWithoutToken(): void
    {
        $event = $this->createMock(TransactionCompletedEvent::class);
        $event->method('getTradable')->willReturn($this->createMock(Crypto::class));
        $event->expects($this->never())->method('getUser');

        $subscriber = new ActivitySubscriber(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MoneyWrapperInterface::class),
            $this->createMock(CryptoRatesFetcherInterface::class),
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->handleTransactionEvent($event, 'deposit.completed');
    }

    /** @dataProvider handleTransactionEventDataProvider */
    public function testHandleTransactionEvent(string $eventName, string $activityClass): void
    {
        $token = $this->createMock(Token::class);
        $user = $this->createMock(User::class);

        $event = $this->createMock(TransactionCompletedEvent::class);
        $event->method('getTradable')->willReturn($token);
        $event->method('getUser')->willReturn($user);
        $event->method('getAmount')->willReturn('1');

        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->with('1', 'TOK')->willReturn(new Money('2', new Currency('TOK')));
        $mw->method('format')
            ->with(
                $this->callback(
                    fn (Money $a) => '3' === $a->getAmount() && 'WEB' === $a->getCurrency()->getCode()
                )
            )
            ->willReturn('4');

        $mw->method('convert')
            ->withConsecutive(
                [
                    $this->callback(fn (Money $a) => '2' === $a->getAmount() && 'TOK' === $a->getCurrency()->getCode()),
                    $this->callback(fn (Currency $c) => 'WEB' === $c->getCode()),
                    $this->callback(
                        fn (FixedExchange $e) =>
                            4.0 === $e->quote(new Currency('TOK'), new Currency('WEB'))->getConversionRatio()
                    ),
                ],
                [
                    $this->callback(fn (Money $a) => '5' === $a->getAmount() && 'WEB' === $a->getCurrency()->getCode()),
                    $this->callback(fn (Currency $c) => 'USD' === $c->getCode()),
                    $this->callback(
                        fn (FixedExchange $e) =>
                            6.0 === $e->quote(new Currency('WEB'), new Currency('USD'))->getConversionRatio()
                    ),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Money('5', new Currency('WEB')),
                new Money('7', new Currency('USD'))
            );

        $crypto = $this->createMock(Crypto::class);
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->expects($this->once())->method('findBySymbol')->willReturn($crypto);

        $ms = $this->createMock(MarketStatus::class);
        $ms->expects($this->once())->method('getLastPrice')->willReturn(new Money('3', new Currency('WEB')));

        $msm = $this->createMock(MarketStatusManagerInterface::class);
        $msm->expects($this->once())
            ->method('getMarketStatus')
            ->with($this->callback(fn (Market $m) => $m->getBase() === $crypto && $m->getQuote() === $token))
            ->willReturn($ms);

        $crf = $this->createMock(CryptoRatesFetcherInterface::class);
        $crf->method('fetch')->willReturn([
            'WEB' => [
                'USD' => '6',
            ],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn (UserAmountActivity $a) => $a instanceof $activityClass
                    && $a->getUser() === $user
                    && $a->getToken() === $token
                    && '7' === $a->getAmount()->getAmount()
                    && 'USD' === $a->getAmount()->getCurrency()->getCode()
            ));

        $subscriber = new ActivitySubscriber(
            $em,
            $mw,
            $crf,
            $msm,
            $cm,
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->handleTransactionEvent($event, $eventName);
    }

    public function handleTransactionEventDataProvider(): array
    {
        return [
            ['deposit.completed', TokenDepositedActivity::class],
            ['withdraw.completed', TokenWithdrawnActivity::class],
        ];
    }

    public function testDonation(): void
    {
        $web = new Currency('WEB');
        $usd = new Currency('USD');

        $token = $this->createMock(Token::class);

        $user = $this->createMock(User::class);

        $amount = new Money('1', $web);

        $donation = $this->createMock(Donation::class);
        $donation->method('getAmount')->willReturn($amount);

        $event = $this->createMock(DonationEvent::class);
        $event->method('getToken')->willReturn($token);
        $event->method('getUser')->willReturn($user);
        $event->method('getDonation')->willReturn($donation);

        $crf = $this->createMock(CryptoRatesFetcherInterface::class);
        $crf->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'WEB' => [
                    'USD' => '2',
                ],
            ]);

        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->expects($this->once())
            ->method('convert')
            ->with(
                $amount,
                $this->callback(fn (Currency $c) => 'USD' === $c->getCode()),
                $this->callback(
                    fn (FixedExchange $e) => 2.0 === $e->quote($web, $usd)->getConversionRatio()
                )
            )
            ->willReturn(new Money('3', $usd));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    fn (DonationActivity $a) => $a->getToken() === $token
                        && $a->getUser() === $user && '3' === $a->getAmount()->getAmount()
                )
            );

        $subscriber = new ActivitySubscriber(
            $em,
            $mw,
            $crf,
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->donation($event, 'donation');
    }

    public function testHandleOrderEventWithoutTokenMarket(): void
    {
        $market = $this->createMock(Market::class);
        $market->expects($this->once())->method('isTokenMarket')->willReturn(false);
        $market->expects($this->never())->method('getQuote');

        $order = $this->createMock(Order::class);
        $order->method('getMarket')->willReturn($market);

        $event = $this->createMock(OrderEventInterface::class);
        $event->method('getOrder')->willReturn($order);

        $subscriber = new ActivitySubscriber(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MoneyWrapperInterface::class),
            $this->createMock(CryptoRatesFetcherInterface::class),
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->handleOrderEvent($event, 'order.completed');
    }

    /** @dataProvider handleOrderEventDataProvider */
    public function testHandleOrderEvent(int $side): void
    {
        $token = $this->createMock(Token::class);

        $market = $this->createMock(Market::class);
        $market->expects($this->once())->method('isTokenMarket')->willReturn(true);
        $market->expects($this->once())->method('getQuote')->willReturn($token);

        $web = new Currency('WEB');
        $tok = new Currency('TOK');
        $usd = new Currency('USD');

        $price = new Money('1', $web);
        $amount = new Money('2', $tok);

        $taker = $this->createMock(User::class);
        $maker = $this->createMock(User::class);

        $order = $this->createMock(Order::class);
        $order->method('getMarket')->willReturn($market);
        $order->method('getPrice')->willReturn($price);
        $order->method('getAmount')->willReturn($amount);
        $order->method('getTaker')->willReturn($taker);
        $order->method('getMaker')->willReturn($maker);
        $order->method('getSide')->willReturn($side);

        $event = $this->createMock(OrderEventInterface::class);
        $event->method('getOrder')->willReturn($order);

        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->expects($this->once())->method('format')->with($amount)->willReturn('3');
        $mw->expects($this->once())
            ->method('convert')
            ->with(
                $this->callback(fn (Money $a) => $a->getAmount() === $price->multiply('3')->getAmount()),
                $this->callback(fn (Currency $c) => 'USD' === $c->getCode()),
                $this->callback(fn (FixedExchange $e) => 4.0 === $e->quote($web, $usd)->getConversionRatio())
            )
            ->willReturn(new Money('5', $usd));

        $crf = $this->createMock(CryptoRatesFetcherInterface::class);
        $crf->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'WEB' => [
                    'USD' => '4',
                ],
            ]);

        $isSellOrder = Order::SELL_SIDE === $side;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    fn (TokenTradedActivity $a) => $a->getToken() === $token
                        && '5' === $a->getAmount()->getAmount()
                        && 'USD' === $a->getAmount()->getCurrency()->getCode()
                        && (
                            ($isSellOrder && $a->getSeller() === $taker && $a->getBuyer() === $maker)
                            || ($a->getSeller() === $maker && $a->getBuyer() === $taker)
                        )
                )
            );

        $subscriber = new ActivitySubscriber(
            $em,
            $mw,
            $crf,
            $this->createMock(MarketStatusManagerInterface::class),
            $this->createMock(CryptoManagerInterface::class),
            $this->createMock(PublisherInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->handleOrderEvent($event, 'order.completed');
    }

    public function handleOrderEventDataProvider(): array
    {
        return [
            [1],
            [2],
        ];
    }
}
