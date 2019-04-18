<?php declare(strict_types = 1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class HackerVoter extends Voter
{
    /** @var bool */
    private $isHackerAllowed;

    public function __construct(bool $isHackerAllowed)
    {
        $this->isHackerAllowed = $isHackerAllowed;
    }

    /** {@inheritdoc} */
    protected function supports($attribute, $subject): bool
    {
        return 'hacker' == $attribute;
    }

    /** {@inheritdoc} */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return $this->isHackerAllowed;
    }
}
