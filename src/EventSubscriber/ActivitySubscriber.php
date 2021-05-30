<?php declare(strict_types = 1);

namespace App\EventSubscriber;

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
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Events\DepositCompletedEvent;
use App\Events\DonationEvent;
use App\Events\OrderEvent;
use App\Events\OrderEventInterface;
use App\Events\PostEvent;
use App\Events\TokenEventInterface;
use App\Events\TokenEvents;
use App\Events\TransactionCompletedEvent;
use App\Events\UserAirdropEvent;
use App\Events\WithdrawCompletedEvent;
use App\Exchange\Factory\MarketFactory;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Mercure\PublisherInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivitySubscriber implements EventSubscriberInterface
{
    private const EVENT_ACTIVITY_MAP = [
        TokenEvents::CREATED => TokenCreatedActivity::class,
        TokenEvents::DEPLOYED => TokenDeployedActivity::class,
        TokenEvents::AIRDROP_CREATED => AirdropCreatedActivity::class,
        TokenEvents::AIRDROP_ENDED => AirdropEndedActivity::class,
        TokenEvents::POST_CREATED => NewPostActivity::class,
        TokenEvents::AIRDROP_CLAIMED => AirdropClaimedActivity::class,
        TokenEvents::DONATION => DonationActivity::class,
        DepositCompletedEvent::NAME => TokenDepositedActivity::class,
        WithdrawCompletedEvent::NAME => TokenWithdrawnActivity::class,
        OrderEvent::COMPLETED => TokenTradedActivity::class,
    ];

    private EntityManagerInterface $entityManager;
    private MoneyWrapperInterface $moneyWrapper;
    private PublisherInterface $publisher;
    private MarketStatusManagerInterface $marketStatusManager;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactory $marketFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        PublisherInterface $publisher,
        MarketStatusManagerInterface $marketStatusManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactory $marketFactory
    ) {
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->publisher = $publisher;
        $this->marketStatusManager = $marketStatusManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::CREATED => 'handleTokenEvent',
            TokenEvents::DEPLOYED => 'handleTokenEvent',
            TokenEvents::AIRDROP_CREATED => 'handleTokenEvent',
            TokenEvents::AIRDROP_ENDED => 'handleTokenEvent',
            TokenEvents::POST_CREATED => 'handlePostEvent',
            TokenEvents::AIRDROP_CLAIMED => 'airdropClaimed',
            TokenEvents::DONATION => 'donation',
            DepositCompletedEvent::NAME => 'handleTransactionEvent',
            WithdrawCompletedEvent::NAME => 'handleTransactionEvent',
            OrderEvent::COMPLETED => 'handleOrderEvent',
        ];
    }

    public function handleTokenEvent(TokenEventInterface $event, string $eventName): void
    {
        $token = $event->getToken();

        $activity = $this->createActivity($eventName)->setToken($token);

        $this->saveActivity($activity);
    }

    public function handlePostEvent(PostEvent $event, string $eventName): void
    {
        $token = $event->getToken();
        $post = $event->getPost();

        /** @var NewPostActivity $activity */
        $activity = $this->createActivity($eventName);

        $activity->setPost($post)->setToken($token);

        $this->saveActivity($activity);
    }

    public function airdropClaimed(UserAirdropEvent $event, string $eventName): void
    {
        $token = $event->getToken();
        $user = $event->getUser();
        $amount = $event->getAirdrop()->getReward();

        /** @var AirdropClaimedActivity $activity */
        $activity = $this->createActivity($eventName);

        $activity->setUser($user)->setAmount($amount)->setToken($token);

        $this->saveActivity($activity);
    }

    public function donation(DonationEvent $event, string $eventName): void
    {
        $token = $event->getToken();
        $user = $event->getUser();
        $amount = $event->getDonation()->getAmount();
        $currency = $event->getDonation()->getCurrency();

        /** @var DonationActivity $activity */
        $activity = $this->createActivity($eventName);

        $activity->setAmount($amount)->setCurrency($currency)->setUser($user)->setToken($token);

        $this->saveActivity($activity);
    }

    public function handleTransactionEvent(TransactionCompletedEvent $event, string $eventName): void
    {
        $token = $event->getTradable();

        if (!$token instanceof Token) {
            return;
        }

        $user = $event->getUser();
        $amount = $this->moneyWrapper->parse($event->getAmount(), Symbols::TOK);

        $lastPrice = $this->getLastPrice($token);
        $lastPrice = $this->moneyWrapper->format($lastPrice);

        $mintmeCurrency = new Currency(Symbols::WEB);

        $amountWorthInMintme = $this->moneyWrapper->convertByRatio(
            $amount,
            $mintmeCurrency,
            $lastPrice
        );

        /** @var TokenDepositedActivity|TokenWithdrawnActivity $activity */
        $activity = $this->createActivity($eventName);

        $activity
            ->setAmount($amountWorthInMintme)
            ->setCurrency($mintmeCurrency->getCode())
            ->setUser($user)
            ->setToken($token);

        $this->saveActivity($activity);
    }

    public function handleOrderEvent(OrderEventInterface $event, string $eventName): void
    {
        $order = $event->getOrder();
        $market = $order->getMarket();

        if (!$market->isTokenMarket()) {
            return;
        }

        /** @var Token $token */
        $token = $market->getQuote();

        $base = $market->getBase();

        $price = $order->getPrice();
        $amount = $order->getAmount();
        $amount = $this->moneyWrapper->format($amount);

        $totalPrice = $price->multiply($amount);

        $currency = $base instanceof Crypto
            ? $base->getSymbol()
            : Symbols::TOK;

        $taker = $order->getTaker();
        $maker = $order->getMaker();
        $isSellOrder = Order::SELL_SIDE === $order->getSide();

        $seller = $isSellOrder
            ? $taker
            : $maker;
        $buyer = $isSellOrder
            ? $maker
            : $taker;

        /** @var TokenTradedActivity $activity */
        $activity = $this->createActivity($eventName);

        $activity
            ->setSeller($seller)
            ->setBuyer($buyer)
            ->setAmount($totalPrice)
            ->setCurrency($currency)
            ->setToken($token)
        ;

        $this->saveActivity($activity);
    }

    private function createActivity(string $eventName): Activity
    {
        $class = self::EVENT_ACTIVITY_MAP[$eventName];

        return new $class();
    }

    private function saveActivity(Activity $activity): void
    {
        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        $this->publishActivity($activity);
    }

    private function publishActivity(Activity $activity): void
    {
        $this->publisher->publish('activities', $activity);
    }

    private function getLastPrice(Token $token): Money
    {
        $base = $this->cryptoManager->findBySymbol(Symbols::WEB);
        $market = $this->marketFactory->create($base, $token);
        $marketStatus = $this->marketStatusManager->getMarketStatus($market);

        return $marketStatus->getLastPrice();
    }
}
