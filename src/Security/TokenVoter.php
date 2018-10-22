<?php

namespace App\Security;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TokenVoter extends Voter
{
    private const EDIT = 'edit';

    /** @var TokenManagerInterface $tokenManager */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return self::EDIT === $attribute && $subject instanceof Token;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /** @var Token $tokenEntity */
        $tokenEntity = $subject;

        return $this->canEdit($tokenEntity);
    }

    private function canEdit(Token $token): bool
    {
        return $this->tokenManager->getOwnToken() === $token;
    }
}
