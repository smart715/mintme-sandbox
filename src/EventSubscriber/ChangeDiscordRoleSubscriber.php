<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\DiscordRoleUser;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Events\OrderEvent;
use App\Events\OrderEventInterface;
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

    private EntityManagerInterface $entityManager;
    private DiscordRoleManagerInterface $discordRoleManager;
    private DiscordManagerInterface $discordManager;
    private array $map = []; // phpcs:ignore
    private array $users;
    private array $tokens;

    public function __construct(
        EntityManagerInterface $entityManager,
        DiscordRoleManagerInterface $discordRoleManager,
        DiscordManagerInterface $discordManager
    ) {
        $this->entityManager = $entityManager;
        $this->discordRoleManager = $discordRoleManager;
        $this->discordManager = $discordManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TokenEvents::AIRDROP_CLAIMED => 'handleTokenUserEvent',
            TokenEvents::DONATION => 'handleTokenUserEvent',
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

        $this->changeRole($user, $token);
    }

    public function handleTransactionEvent(TransactionCompletedEvent $event): void
    {
        $token = $event->getTradable();

        if (!$token instanceof Token) {
            return;
        }

        $user = $event->getUser();

        $this->changeRole($user, $token);
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

        $this->changeRole($maker, $token);

        if ($taker) {
            $this->changeRole($taker, $token);
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

    public function changeRolesOnTerminate(): void
    {
        foreach ($this->map as $userId => $tokenIds) {
            foreach ($tokenIds as $tokenId) {
                $user = $this->users[$userId];
                $token = $this->tokens[$tokenId];

                $this->changeRole($user, $token);
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

    private function changeRole(User $user, Token $token): void
    {
        if (!$token->getDiscordConfig()->getEnabled()
            || !$token->getDiscordConfig()->getSpecialRolesEnabled()
            || !$user->isSignedInWithDiscord()
            || $token->isOwner($user->getProfile()->getTokens())
        ) {
            return;
        }

        $this->entityManager->refresh($user);

        $dru = $user->getDiscordRoleUser($token);

        $currentRole = $dru
            ? $dru->getDiscordRole()
            : null;

        $newRole = $this->discordRoleManager->findRoleOfUser($user, $token);

        if ($currentRole === $newRole) {
            return;
        }

        if ($currentRole) {
            try {
                $this->discordManager->removeGuildMemberRole($user, $currentRole);
            } catch (\Throwable $e) {
                return;
            }
        }

        if ($newRole) {
            try {
                $this->discordManager->addGuildMemberRole($user, $newRole);
            } catch (\Throwable $e) {
                return;
            }

            $dru = $dru
                ? $dru->setDiscordRole($newRole)
                : (new DiscordRoleUser())->setDiscordRole($newRole)->setUser($user);

            $this->entityManager->persist($dru);
        } else {
            $this->entityManager->remove($dru);
        }

        $this->entityManager->flush();
    }
}
