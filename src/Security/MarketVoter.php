<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Manager\TokenCryptoManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MarketVoter extends Voter
{
    private const OPERATE_MARKET = 'operate';
    private const CREATE_MARKET = 'create';
    private const ACTIONS = [
        self::OPERATE_MARKET,
        self::CREATE_MARKET,
    ];
    private TokenCryptoManagerInterface $tokenCryptoManager;

    public function __construct(TokenCryptoManagerInterface $tokenCryptoManager)
    {
        $this->tokenCryptoManager = $tokenCryptoManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Market;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Market $subject */
        if (self::OPERATE_MARKET === $attribute && $subject->getQuote() instanceof Token) {
            return $this->doesMarketExists($subject);
        }

        if (self::CREATE_MARKET === $attribute) {
            return !$this->doesMarketExists($subject);
        }

        return true;
    }

    private function doesMarketExists(Market $market): bool
    {
        /** @var Crypto $base */
        $base = $market->getBase();
        /** @var Token $quote */
        $quote = $market->getQuote();

        return (bool)$this->tokenCryptoManager->getByCryptoAndToken($base, $quote);
    }
}
