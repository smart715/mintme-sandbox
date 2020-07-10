<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TokenVoter extends Voter
{
    private const EDIT = 'edit';
    private const DELETE = 'delete';
    private const NOT_BLOCKED = 'not-blocked';

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
        return in_array($attribute, [self::NOT_BLOCKED, self::EDIT, self::DELETE], true)
            && ($subject instanceof Token || $subject instanceof Crypto || is_null($subject));
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @psalm-suppress UndefinedDocblockClass */
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if (self::NOT_BLOCKED === $attribute) {
            $token = $user->getProfile()->getToken();

            if (!$subject || $subject instanceof Crypto) {
                return !$user->isBlocked();
            }

            if ($subject instanceof Token && $subject->isBlocked()) {
                return false;
            }

            return $token
                ? !$token->isBlocked()
                : true;
        }

        /** @var Token $tokenEntity */
        $tokenEntity = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->ownToken($tokenEntity) && !$tokenEntity->isBlocked();
            case self::DELETE:
                return $this->ownToken($tokenEntity) && !$tokenEntity->isBlocked();
        }

        return false;
    }

    private function ownToken(Token $token): bool
    {
        return $this->tokenManager->getOwnToken() === $token;
    }
}
