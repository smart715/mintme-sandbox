<?php declare(strict_types = 1);

namespace App\Security;

use App\Config\UserLimitsConfig;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OAuthVoter extends Voter
{
    private UserLimitsConfig $userLimitsConfig;
    private const CREATE_OAUTH = 'create-oauth';
    private const ACTIONS = [
        self::CREATE_OAUTH,
    ];

    public function __construct(UserLimitsConfig $userLimitsConfig)
    {
        $this->userLimitsConfig = $userLimitsConfig;
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

        if (self::CREATE_OAUTH === $attribute) {
            return $this->canCreateOAuth($user);
        }

        return false;
    }

    private function canCreateOAuth(User $user): bool
    {
        $clients = $user->getApiClients();
        $oauthKeysLimit = $this->userLimitsConfig->getMaxClientsLimit();

        return count($clients) < $oauthKeysLimit;
    }
}
