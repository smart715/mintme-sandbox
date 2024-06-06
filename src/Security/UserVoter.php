<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const NOT_BLOCKED = 'not-blocked';
    public const ADD_COMMENT = 'add-comment';
    private const ACTIONS = [
        self::NOT_BLOCKED,
        self::ADD_COMMENT,
    ];

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof User;
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

        switch ($attribute) {
            case self::NOT_BLOCKED:
                return !$user->isBlocked();
            case self::ADD_COMMENT:
                return $this->canAddComment($user);
            default:
                return false;
        }
    }
    private function canAddComment(User $user): bool
    {
        return $user instanceof User && $user->hasRole(User::ROLE_AUTHENTICATED);
    }
}
