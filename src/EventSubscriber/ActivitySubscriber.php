<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Activity\Factory\ActivityFactory;
use App\Entity\Activity;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\BonusEventActivity;
use App\Events\Activity\OrderEventActivity;
use App\Events\Activity\RewardEventActivity;
use App\Events\Activity\SignupBonusActivity;
use App\Events\Activity\TipTokenEventActivity;
use App\Events\Activity\TokenEventActivity;
use App\Events\Activity\TokenImportedEvent;
use App\Events\Activity\TokenReleaseActivityEvent;
use App\Events\Activity\UserEventActivity;
use App\Events\Activity\UserTokenEventActivity;
use App\Events\Activity\UserVotingEventActivity;
use App\Events\Activity\VotingEventActivity;
use App\Events\DepositCompletedEvent;
use App\Events\MarketEvent;
use App\Events\OrderEvent;
use App\Events\PostEvent;
use App\Events\RewardEvent;
use App\Events\TokenEvents;
use App\Events\TransactionCompletedEvent;
use App\Events\UserAirdropEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mercure\PublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivitySubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private PublisherInterface $publisher;
    private ActivityFactory $activityFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        PublisherInterface $publisher,
        ActivityFactory $activityFactory
    ) {
        $this->entityManager = $entityManager;
        $this->publisher = $publisher;
        $this->activityFactory = $activityFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CLAIMED => 'handleAirdropClaimed',
            TokenEvents::MARKET_CREATED => 'handleMarketEvent',
            OrderEvent::COMPLETED => 'handleOrderEvent',
            UserEventActivity::NAME => 'handleUserEvent',
            TokenEvents::POST_SHARED => 'handlePostSharedEvent',

            TokenEvents::AIRDROP_CREATED => 'handleTokenEvent',
            TokenEvents::AIRDROP_ENDED => 'handleTokenEvent',
            TokenEvents::DEPLOYED => 'handleTokenEvent',
            TokenEvents::CONNECTED => 'handleTokenEvent',
            TokenImportedEvent::NAME => 'handleTokenEvent',
            TokenEvents::CREATED => 'handleTokenEvent',
            TokenEventActivity::NAME => 'handleTokenEvent',
            SignupBonusActivity::NAME => 'handleTokenEvent',
            VotingEventActivity::NAME => 'handleTokenEvent',
            UserVotingEventActivity::NAME => 'handleTokenEvent',
            TipTokenEventActivity::NAME => 'handleTokenEvent',
            TokenReleaseActivityEvent::NAME => 'handleTokenEvent',
            TokenEvents::POST_CREATED => 'handleTokenEvent',

            // events where a user interacts with a token
            TokenEvents::DONATION => 'handleUserTokenEvent',
            TokenEvents::POST_LIKED => 'handleUserTokenEvent',
            TokenEvents::POST_COMMENTED => 'handleUserTokenEvent',
            TokenEvents::COMMENT_LIKE => 'handleUserTokenEvent',
            TokenEvents::NEW_DM => 'handleUserTokenEvent',
            UserTokenEventActivity::NAME => 'handleUserTokenEvent',
            BonusEventActivity::NAME => 'handleUserTokenEvent',

            DepositCompletedEvent::NAME => 'handleTransactionEvent',
            WithdrawCompletedEvent::NAME => 'handleTransactionEvent',

            RewardEvent::REWARD_NEW => 'handleRewardEvent',
            RewardEvent::PARTICIPANT_ADDED => 'handleRewardEvent',
            RewardEvent::VOLUNTEER_NEW => 'handleRewardEvent',
            RewardEvent::VOLUNTEER_ACCEPTED => 'handleRewardEvent',
            RewardEvent::VOLUNTEER_COMPLETED => 'handleRewardEvent',
        ];
    }

    public function handleUserEvent(UserEventActivity $event): void
    {
        $this->initActivity($event);
    }

    public function handleTokenEvent(TokenEventActivity $event): void
    {
        if ($event->getToken()->isQuiet() ||
            !$event->getToken()->getOwner() ||
            !$event->getToken()->getOwner()->hasRole(User::ROLE_AUTHENTICATED)
        ) {
            return;
        }

        $this->initActivity($event);
    }

    public function handleUserTokenEvent(UserTokenEventActivity $event): void
    {
        if ($event->getToken()->isQuiet() || !$event->getUser()->hasRole(User::ROLE_AUTHENTICATED)) {
            return;
        }

        $this->initActivity($event);
    }

    public function handleAirdropClaimed(UserAirdropEvent $event): void
    {
        if ($event->getToken()->isQuiet() || !$event->getUser()->hasRole(User::ROLE_AUTHENTICATED)) {
            return;
        }

        $this->initActivity($event);
    }

    public function handleRewardEvent(RewardEventActivity $event): void
    {
        if ($event->getReward()->getToken()->isQuiet() ||
            ($event->getRewardMember() && !$event->getRewardMember()->getUser()->hasRole(User::ROLE_AUTHENTICATED))
        ) {
            return;
        }

        $this->initActivity($event);
    }

    public function handlePostSharedEvent(PostEvent $event): void
    {
        if (!$event->getPost()->getShareReward()->isZero()) {
            return;
        }

        $this->handleUserTokenEvent($event);
    }

    public function handleTransactionEvent(TransactionCompletedEvent $event): void
    {
        $token = $event->getTradable();

        if (!$token instanceof Token || $token->isQuiet() || !$event->getUser()->hasRole(User::ROLE_AUTHENTICATED)) {
            return;
        }

        $this->initActivity($event);
    }

    public function handleOrderEvent(OrderEventActivity $event): void
    {
        $order = $event->getOrder();
        $market = $order->getMarket();
        $token = $market->getQuote();

        if (!$token instanceof Token || $token->isQuiet()) {
            return;
        }

        $this->initActivity($event);
    }

    public function handleMarketEvent(MarketEvent $event): void
    {
        if ($event->getToken()->isQuiet() ||
            !$event->getToken()->getOwner() ||
            !$event->getToken()->getOwner()->hasRole(User::ROLE_AUTHENTICATED)) {
            return;
        }

        $this->initActivity($event);
    }

    private function initActivity(ActivityEventInterface $event): void
    {
        $activity = $this->createActivity($event);
        $this->saveActivity($activity);
        $this->publishActivity($activity);
    }

    private function createActivity(ActivityEventInterface $activityEvent): Activity
    {
        return $this->activityFactory->create($activityEvent);
    }

    private function saveActivity(Activity $activity): void
    {
        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }

    private function publishActivity(Activity $activity): void
    {
        $this->publisher->publish('activities', $activity);
    }
}
