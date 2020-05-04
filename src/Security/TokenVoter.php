<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TokenVoter extends Voter
{
    private const EDIT = 'edit';
    private const DELETE = 'delete';

    /** @var TokenManagerInterface $tokenManager */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Token;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /** @var Token $tokenEntity */
        $tokenEntity = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->ownToken($tokenEntity);
            case self::DELETE:
                return $this->ownToken($tokenEntity);
        }

        return false;
    }

    private function ownToken(Token $token): bool
    {
        return $this->tokenManager->getOwnToken() === $token;
    }
}
