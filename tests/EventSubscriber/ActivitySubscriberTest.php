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
use App\Exchange\Factory\MarketFactoryInterface;
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

        $subscriber = $this->mockActivitySubscriber($em);

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

        $subscriber = $this->mockActivitySubscriber($em);

        $subscriber->airdropClaimed($event, 'airdrop.claimed');
    }

    public function testHandleTransactionEventWithoutToken(): void
    {
        $event = $this->createMock(TransactionCompletedEvent::class);
        $event->method('getTradable')->willReturn($this->createMock(Crypto::class));
        $event->expects($this->never())->method('getUser');

        $subscriber = $this->mockActivitySubscriber();

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
        $mw->expects($this->once())->method('parse');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn (UserAmountActivity $a) => $a instanceof $activityClass
                    && $a->getUser() === $user
                    && $a->getToken() === $token
                    && '1000000000000' === $a->getAmount()->getAmount()
                    && 'TOK' === $a->getAmount()->getCurrency()->getCode()
            ));

        $subscriber = $this->mockActivitySubscriber($em, $mw);

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

        $token = $this->createMock(Token::class);

        $user = $this->createMock(User::class);

        $amount = new Money('1', $web);

        $donation = $this->createMock(Donation::class);
        $donation->method('getAmount')->willReturn($amount);
        $donation->method('getCurrency')->willReturn('WEB');

        $event = $this->createMock(DonationEvent::class);
        $event->method('getToken')->willReturn($token);
        $event->method('getUser')->willReturn($user);
        $event->method('getDonation')->willReturn($donation);


        $mw = $this->createMock(MoneyWrapperInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    fn (DonationActivity $a) => $a->getToken() === $token
                        && $a->getUser() === $user && '1' === $a->getAmount()->getAmount()
                )
            );

        $subscriber = $this->mockActivitySubscriber($em, $mw);

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

        $subscriber = $this->mockActivitySubscriber();

        $subscriber->handleOrderEvent($event, 'order.completed');
    }

    /** @dataProvider handleOrderEventDataProvider */
    public function testHandleOrderEvent(int $side): void
    {
        $token = $this->createMock(Token::class);

        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn('WEB');

        $market = $this->createMock(Market::class);
        $market->expects($this->once())->method('isTokenMarket')->willReturn(true);
        $market->expects($this->once())->method('getQuote')->willReturn($token);
        $market->expects($this->once())->method('getBase')->willReturn($crypto);

        $web = new Currency('WEB');
        $tok = new Currency('TOK');

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
        $mw->expects($this->once())->method('format')->with($amount)->willReturn('2');

        $isSellOrder = Order::SELL_SIDE === $side;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    fn (TokenTradedActivity $a) => $a->getToken() === $token
                        && '2' === $a->getAmount()->getAmount()
                        && 'WEB' === $a->getAmount()->getCurrency()->getCode()
                        && (
                            ($isSellOrder && $a->getSeller() === $taker && $a->getBuyer() === $maker)
                            || ($a->getSeller() === $maker && $a->getBuyer() === $taker)
                        )
                )
            );

        $subscriber = $this->mockActivitySubscriber($em, $mw);

        $subscriber->handleOrderEvent($event, 'order.completed');
    }

    public function handleOrderEventDataProvider(): array
    {
        return [
            [1],
            [2],
        ];
    }

    public function mockActivitySubscriber(
        ?EntityManagerInterface $em = null,
        ?MoneyWrapperInterface $mw = null,
        ?PublisherInterface $pu = null,
        ?MarketStatusManagerInterface $ms = null,
        ?CryptoManagerInterface $cm = null,
        ?MarketFactoryInterface $mf = null
    ): ActivitySubscriber {
        return new ActivitySubscriber(
            $em ?? $this->createMock(EntityManagerInterface::class),
            $mw ?? $this->createMock(MoneyWrapperInterface::class),
            $pu ?? $this->createMock(PublisherInterface::class),
            $ms ?? $this->createMock(MarketStatusManagerInterface::class),
            $cm ?? $this->createMock(CryptoManagerInterface::class),
            $mf ?? $this->createMock(MarketFactoryInterface::class)
        );
    }
}
