<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TradingVoter extends Voter
{
    private ContainerInterface $container;
    private const MAKE_ORDER = 'make-order';
    private const SELL_ORDER = 'sell-order';
    private const ACTIONS = [
        self::MAKE_ORDER,
        self::SELL_ORDER,
    ];
    private AccessDecisionManagerInterface $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager, ContainerInterface $container)
    {
        $this->decisionManager = $decisionManager;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Market;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (self::MAKE_ORDER === $attribute) {
            return $this->canMakeOrder($token);
        }

        if (self::SELL_ORDER === $attribute) {
            return $this->canSellOrder($token, $subject);
        }

        return false;
    }

    private function canMakeOrder(TokenInterface $token): bool
    {
        if ($this->container->getParameter('auth_make_disable_trading')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }

    private function canSellOrder(TokenInterface $token, Market $market): bool
    {
        /** @var Token|Crypto $tradable */
        $tradable = $market->getQuote();

        /** @var User $user */
        $user = $token->getUser();

        if (($tradable instanceof Crypto || $tradable->getOwner()->getId() !== $user->getId())
            && $this->container->getParameter('auth_make_disable_sell')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }
}
