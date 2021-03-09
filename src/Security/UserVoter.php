<?php declare(strict_types = 1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
    private const AUTHENTICATED = 'authenticated';
    private const SEMI_AUTHENTICATED = 'semi-authenticated';
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
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->security->isGranted('ROLE_SUPER_ADMIN') ||
            $this->security->isGranted('ROLE_AUTHENTICATED')
        ) {
            return true;
        }
    }
}
