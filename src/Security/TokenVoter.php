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
        if (!in_array($attribute, [self::NOT_BLOCKED, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof Token || $subject instanceof Crypto || is_null($subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $securityToken): bool
    {
        /** @psalm-suppress UndefinedDocblockClass */
        $user = $securityToken->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if (!$subject || $subject instanceof Crypto) {
            return !$user->isBlocked();
        }

        /** @var Token $cryptoToken */
        $cryptoToken = $subject;

        switch ($attribute) {
            case self::NOT_BLOCKED:
                return !$cryptoToken->isBlocked();
            case self::EDIT:
            case self::DELETE:
                return $cryptoToken->isOwner($this->tokenManager->getOwnTokens()) && !$cryptoToken->isBlocked();
        }

        return false;
    }
}
