<?php declare(strict_types = 1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class HackerVoter extends Voter
{
    /** @var RequestStack */
    private $requestStack;

    /** @var bool */
    private $isHackerAllowed;

    public function __construct(RequestStack $requestStack, bool $isHackerAllowed)
    {
        $this->requestStack = $requestStack;
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
        return $this->isHackerAllowed && $this->isHostAllowed(
                $this->requestStack->getCurrentRequest()->getHost()
            );
    }

    private function isHostAllowed(string $host): bool
    {
        return preg_match($this->buildHostPattern(), $host);
    }

    private function buildHostPattern(): string
    {
        return "/^(localhost|[\w\-]{1,}\.mintme\.abchosting\.(abc|org))$/";
    }
}
