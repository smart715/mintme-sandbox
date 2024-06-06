<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TwoFactorVoter extends Voter
{
    private const TWO_FA_LOGIN = '2fa-login';
    public const TWO_FA_ENABLE = '2fa-enable';

    private const ACTIONS = [
        self::TWO_FA_LOGIN,
        self::TWO_FA_ENABLE,
    ];

    private TwoFactorManagerInterface $twoFactorManager;
    private AccessDecisionManagerInterface $decisionManager;
    private ContainerInterface $container;

    public function __construct(
        TwoFactorManagerInterface $twoFactorManager,
        AccessDecisionManagerInterface $decisionManager,
        ContainerInterface $container
    ) {
        $this->twoFactorManager = $twoFactorManager;
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
        switch ($attribute) {
            case self::TWO_FA_LOGIN:
                return $this->canLoginTwofa($subject, $token);
            case self::TWO_FA_ENABLE:
                return $this->canEnableTwofa($token);
            default:
                return false;
        }
    }

    /**
     * @param mixed $subject
     */
    private function canLoginTwofa($subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        $code = $subject ?? '';

        return !$user->isGoogleAuthenticatorEnabled() || $this->twoFactorManager->checkCode($user, $code);
    }

    private function canEnableTwofa(TokenInterface $token): bool
    {
        if ($this->container->getParameter('auth_make_disable_2fa')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }
}
