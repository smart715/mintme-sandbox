<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Security\Config\DisabledServicesConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TradingVoter extends Voter
{
    private ContainerInterface $container;
    private const MAKE_ORDER = 'make-order';
    private const SELL_ORDER = 'sell-order';
    private const TRADES_ENABLED = 'trades-enabled';
    public const ALL_ORDERS_ENABLED = 'all-orders-enabled';

    private const ACTIONS = [
        self::MAKE_ORDER,
        self::SELL_ORDER,
        self::TRADES_ENABLED,
        self::ALL_ORDERS_ENABLED,
    ];
    private AccessDecisionManagerInterface $decisionManager;
    private DisabledServicesConfig $disabledServicesConfig;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        DisabledServicesConfig $disabledServicesConfig,
        ContainerInterface $container
    ) {
        $this->decisionManager = $decisionManager;
        $this->disabledServicesConfig = $disabledServicesConfig;
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
            return $this->canMakeOrder($token, $subject);
        }

        if (self::SELL_ORDER === $attribute) {
            return $this->canSellOrder($token, $subject);
        }

        if (self::TRADES_ENABLED === $attribute) {
            return $this->canMakeTrades($subject);
        }

        if (self::ALL_ORDERS_ENABLED === $attribute) {
            return $this->canMakeCommonAndQuickOrders($token, $subject);
        }

        return false;
    }

    private function canMakeTrades(Market $market): bool
    {
        /** @var Token|Crypto $quote */
        $quote = $market->getQuote();

        /** @var Crypto $base */
        $base = $market->getBase();

        return !$this->disabledServicesConfig->isCryptoTradesDisabled($base->getSymbol()) && (
            $quote instanceof Token && !$quote->getTradesDisabled()
            || $quote instanceof Crypto && !$this->disabledServicesConfig->isCryptoTradesDisabled($quote->getSymbol())
        );
    }

    private function canMakeCommonAndQuickOrders(TokenInterface $token, Market $market): bool
    {
        $tradable = $market->getQuote();
        /** @var User $user */
        $user = $token->getUser();

        if (!$tradable instanceof Token) {
            return true;
        }

        $isOwner = $user->getId() === $tradable->getProfile()->getUser()->getId();
        $deployedToken = $tradable->isDeployed();

        return $isOwner || $deployedToken;
    }

    private function canMakeOrder(TokenInterface $token, Market $market): bool
    {
        if ($this->container->getParameter('auth_make_disable_trading')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }

    private function canSellOrder(TokenInterface $token, Market $market): bool
    {
        /** @var Token|Crypto $quote */
        $quote = $market->getQuote();

        /** @var User $user */
        $user = $token->getUser();

        if (($quote instanceof Crypto || $quote->getOwner()->getId() !== $user->getId())
            && $this->container->getParameter('auth_make_disable_sell')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }
}
