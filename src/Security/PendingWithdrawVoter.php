<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\PendingWithdrawInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PendingWithdrawVoter extends Voter
{
    private const EDIT = 'edit';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof PendingWithdrawInterface;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @psalm-suppress UndefinedDocblockClass */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->getId() === $subject->getUser()->getId();
    }
}
