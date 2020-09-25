<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Security\Config\DisabledBlockchainConfig;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DisabledBlockchainVoter extends Voter
{
    private const NOT_DISABLED = 'not-disabled';

    /** @var DisabledBlockchainConfig */
    private $disabledConfig;

    public function __construct(DisabledBlockchainConfig $disabledBlockchainConfig)
    {
        $this->disabledConfig = $disabledBlockchainConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, [self::NOT_DISABLED])) {
            return false;
        }

        return $subject instanceof Crypto;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Crypto $subject */
        return !in_array($subject->getSymbol(), $this->disabledConfig->getDisabledCryptoSymbols());
    }
}
