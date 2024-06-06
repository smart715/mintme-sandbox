<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepositWithdrawalVoter extends Voter
{
    private ContainerInterface $container;
    private const MAKE_DEPOSIT = 'make-deposit';
    private const MAKE_WITHDRAWAL = 'make-withdrawal';
    private const CONFIRM_WITHDRAWAL = 'confirm-withdrawal';
    private const ACTIONS = [
        self::MAKE_DEPOSIT,
        self::MAKE_WITHDRAWAL,
        self::CONFIRM_WITHDRAWAL,
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
        return in_array($attribute, self::ACTIONS);
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

        if (self::MAKE_DEPOSIT === $attribute) {
            return $this->canMakeDepositOrWithdrawal(
                $this->container->getParameter('auth_make_disable_deposit'),
                $token
            );
        }

        if (self::MAKE_WITHDRAWAL === $attribute) {
            return $this->canMakeDepositOrWithdrawal(
                $this->container->getParameter('auth_make_disable_withdrawals'),
                $token
            );
        }

        if (self::CONFIRM_WITHDRAWAL === $attribute) {
            return !$user->isBlocked();
        }

        return false;
    }
    
    private function canMakeDepositOrWithdrawal(bool $authMakeDisable, TokenInterface $token): bool
    {
        if ($authMakeDisable) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }
}
