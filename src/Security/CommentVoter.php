<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    private const EDIT = 'edit';
    private const DELETE = 'delete';
    private const ACTIONS = [
        self::EDIT,
        self::DELETE,
    ];

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Comment;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $user = $user instanceof User
            ? $user
            : null;

        /** @var Comment */
        $comment = $subject;

        if (self::EDIT === $attribute) {
            return $this->canEdit($comment, $user);
        }

        if (self::DELETE === $attribute) {
            return $this->canDelete($comment, $user);
        }

        return false;
    }

    private function canEdit(Comment $comment, ?User $user): bool
    {
        return $comment->getAuthor() === $user;
    }

    private function canDelete(Comment $comment, ?User $user): bool
    {
        return $comment->getPost()->getAuthor()->getUser()->getId() === $user->getId()
            || $comment->getAuthor()->getId() === $user->getId();
    }
}
