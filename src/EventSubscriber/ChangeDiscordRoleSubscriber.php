<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\DiscordRoleUser;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Events\OrderEvent;
use App\Events\OrderEventInterface;
use App\Events\PostEvent;
use App\Events\RewardEvent;
use App\Events\TokenEvents;
use App\Events\TokenUserEventInterface;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ChangeDiscordRoleSubscriber implements EventSubscriberInterface
{
    private DiscordManagerInterface $discordManager;
    private array $map = []; // phpcs:ignore
    private array $users;
    private array $tokens;

    public function __construct(
        DiscordManagerInterface $discordManager
    ) {
        $this->discordManager = $discordManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CLAIMED => 'handleTokenUserEvent',
            TokenEvents::DONATION => 'handleTokenUserEvent',
            TokenEvents::POST_SHARED => 'handlePostSharedEvent',
            RewardEvent::PARTICIPANT_ADDED => 'handleRewardEvent',
            RewardEvent::VOLUNTEER_COMPLETED => 'handleRewardEvent',
            DepositCompletedEvent::NAME => 'handleTransactionEvent',
            WithdrawCompletedEvent::NAME => 'handleTransactionEvent',
            OrderEvent::COMPLETED => 'handleOrderEvent',
            OrderEvent::CREATED => 'handleOrderEvent',
            OrderEvent::CANCELLED => 'handleCancelledOrderEvent',
            KernelEvents::TERMINATE => 'changeRolesOnTerminate',
        ];
    }

    public function handleTokenUserEvent(TokenUserEventInterface $event): void
    {
        $token = $event->getToken();
        $user = $event->getUser();

        $this->discordManager->updateRoleOfUser($user, $token);
    }

    public function handleTransactionEvent(TransactionCompletedEvent $event): void
    {
        $token = $event->getTradable();

        if (!$token instanceof Token) {
            return;
        }

        $user = $event->getUser();

        $this->discordManager->updateRoleOfUser($user, $token);
    }

    public function handleOrderEvent(OrderEventInterface $event): void
    {
        $order = $event->getOrder();
        $market = $order->getMarket();

        if (!$market->isTokenMarket()) {
            return;
        }

        /** @var Token $token */
        $token = $market->getQuote();

        $taker = $order->getTaker();
        $maker = $order->getMaker();

        $this->discordManager->updateRoleOfUser($maker, $token);

        if ($taker) {
            $this->discordManager->updateRoleOfUser($taker, $token);
        }
    }

    public function handleCancelledOrderEvent(OrderEventInterface $event): void
    {
        $order = $event->getOrder();
        $market = $order->getMarket();

        if (!$market->isTokenMarket()) {
            return;
        }

        /** @var Token $token */
        $token = $market->getQuote();

        $user = $order->getMaker();

        // When a lot of orders were cancelled all at once the role was being changed everytime
        // So this is to wait until after the request was finished to finally change the role
        $this->addUserAndTokenToHandleOnTerminate($user, $token);
    }

    public function handlePostSharedEvent(PostEvent $event): void
    {
        $reward = $event->getPost()->getShareReward();

        if ($reward->isZero()) {
            return;
        }

        $user = $event->getUser();
        $token = $event->getToken();

        $this->addUserAndTokenToHandleOnTerminate($user, $token);
    }

    public function handleRewardEvent(RewardEvent $event): void
    {
        $member = $event->getRewardMember();

        if (!$member) {
            return;
        }

        $user = $member->getUser();
        $token = $event->getReward()->getToken();

        $this->addUserAndTokenToHandleOnTerminate($user, $token);
    }

    public function changeRolesOnTerminate(): void
    {
        foreach ($this->map as $userId => $tokenIds) {
            foreach ($tokenIds as $tokenId) {
                $user = $this->users[$userId];
                $token = $this->tokens[$tokenId];

                $this->discordManager->updateRoleOfUser($user, $token);
            }
        }
    }

    private function addUserAndTokenToHandleOnTerminate(User $user, Token $token): void
    {
        if (!isset($this->users[$user->getId()])) {
            $this->users[$user->getId()] = $user;
        }

        if (!isset($this->tokens[$token->getId()])) {
            $this->tokens[$token->getId()] = $token;
        }

        if (!isset($this->map[$user->getId()])) {
            $this->map[$user->getId()] = [];
        }

        if (!in_array($token->getId(), $this->map[$user->getId()], true)) {
            $this->map[$user->getId()][] = $token->getId();
        }
    }
}
