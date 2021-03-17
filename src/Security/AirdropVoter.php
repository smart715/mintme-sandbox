<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AirdropVoter extends Voter
{
    private const CLAIM = 'claim';
    private const ACTIONS = [
        self::CLAIM,
    ];
    private AccessDecisionManagerInterface $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Airdrop;
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

        if (self::CLAIM === $attribute) {
            return $this->canClaim($token);
        }

        return false;
    }

    private function canClaim(TokenInterface $token): bool
    {
        return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
    }
}
