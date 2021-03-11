<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
    private const AUTHENTICATED = 'ROLE_AUTHENTICATED';
    private const SEMI_AUTHENTICATED = 'ROLE_SEMI_AUTHENTICATED';
    private const USER_ROLES = [
        self::AUTHENTICATED,
        self::SEMI_AUTHENTICATED,
    ];

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, self::USER_ROLES);
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

        return $attribute === $this->security->isGranted('ROLE_AUTHENTICATED');
    }
}
