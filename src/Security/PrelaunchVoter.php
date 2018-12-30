<?php

namespace App\Security;

use App\Exchange\Trade\Config\PrelaunchConfig;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PrelaunchVoter extends Voter
{
    /** @var PrelaunchConfig */
    private $prelaunchConfig;

    /** @var string */
    private $env;

    public function __construct(PrelaunchConfig $prelaunchConfig, string $env)
    {
        $this->prelaunchConfig = $prelaunchConfig;
        $this->env = $env;
    }

    /** {@inheritdoc} */
    protected function supports($attribute, $subject): bool
    {
        return 'prelaunch' == $attribute;
    }

    /** {@inheritdoc} */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if ('dev' === $this->env) {
            return true;
        }

        return !$this->prelaunchConfig->isEnabled();
    }
}
