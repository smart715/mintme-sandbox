<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostRewardVoter extends Voter
{
    private ContainerInterface $container;
    private const COLLECT_REWARD = 'collect-reward';
    private const ACTIONS = [
        self::COLLECT_REWARD,
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
        return in_array($attribute, self::ACTIONS, true);
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

        if (self::COLLECT_REWARD === $attribute) {
            return $this->canCollectReward($token);
        }

        return false;
    }

    private function canCollectReward(TokenInterface $token): bool
    {
        if ($this->container->getParameter('auth_make_disable_post_reward')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }
        
        return true;
    }
}
