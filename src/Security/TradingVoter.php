<?php declare(strict_types = 1);

namespace App\Security;

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
    private const ACTIONS = [
        self::MAKE_ORDER,
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

        return false;
    }

    private function canMakeOrder(TokenInterface $token): bool
    {
        if ($this->container->getParameter('auth_make_disable_trading')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }
}
