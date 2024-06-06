<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Rewards\Reward;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RewardVoter extends Voter
{
    public const ADD = 'add';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const ACCEPT_MEMBER = 'accept-member';
    public const ADD_MEMBER = 'add-member';
    private const ACTIONS = [
        self::ADD,
        self::EDIT,
        self::DELETE,
        self::ACCEPT_MEMBER,
        self::ADD_MEMBER,
    ];

    /** {@inheritDoc}} */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Reward;
    }

    /** {@inheritDoc}} */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user) {
            return false;
        }

        if (self::ADD_MEMBER === $attribute) {
            return !$this->isOwner($user, $subject);
        }

        if (self::ADD === $attribute ||
            self::EDIT === $attribute ||
            self::DELETE === $attribute ||
            self::ACCEPT_MEMBER === $attribute
        ) {
            return $this->isOwner($user, $subject);
        }

        return false;
    }

    private function isOwner(User $user, Reward $reward): bool
    {
        return $user === $reward->getToken()->getOwner();
    }
}
