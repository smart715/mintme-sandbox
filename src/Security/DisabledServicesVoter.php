<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Symbols;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DisabledServicesVoter extends Voter
{
    public const COIN_DEPOSIT = 'coin-deposit';
    public const COIN_WITHDRAW = 'coin-withdraw';
    public const TOKEN_DEPOSIT = 'token-deposit';
    public const TOKEN_WITHDRAW = 'token-withdraw';
    public const DEPLOY = 'deploy';
    public const NEW_TRADES = 'new-trades';
    public const TRADING = 'trading';

    private const ALL_ACTIONS = [
        self::COIN_DEPOSIT,
        self::COIN_WITHDRAW,
        self::TOKEN_DEPOSIT,
        self::TOKEN_WITHDRAW,
        self::DEPLOY,
        self::NEW_TRADES,
        self::TRADING,
    ];

    /** @var DisabledServicesConfig */
    private DisabledServicesConfig $disabledServicesConfig;

    public function __construct(DisabledServicesConfig $disabledServicesConfig)
    {
        $this->disabledServicesConfig = $disabledServicesConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ALL_ACTIONS);
    }

    /**
     * {@inheritdoc}
     *
     * @param Crypto|Token|null $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        switch ($attribute) {
            case self::COIN_DEPOSIT:
                return $subject instanceof Crypto
                    ? !$this->disabledServicesConfig->isCryptoDepositDisabled($subject->getSymbol())
                    : !$this->disabledServicesConfig->isCoinDepositsDisabled();
            case self::COIN_WITHDRAW:
                return $subject instanceof Crypto
                    ? !$this->disabledServicesConfig->isCryptoWithdrawalDisabled($subject->getSymbol())
                    : !$this->disabledServicesConfig->isCoinWithdrawalsDisabled();
            case self::TOKEN_DEPOSIT:
                return !$this->disabledServicesConfig->isTokenDepositsDisabled()
                    && (!$subject instanceof Token || !$subject->getDepositsDisabled());
            case self::TOKEN_WITHDRAW:
                return !$this->disabledServicesConfig->isTokenWithdrawalsDisabled()
                    && (!$subject instanceof Token || !$subject->getWithdrawalsDisabled());
            case self::DEPLOY:
                return !$this->disabledServicesConfig->isDeployDisabled();
            case self::NEW_TRADES:
                return !$this->disabledServicesConfig->isNewTradesDisabled();
            case self::TRADING:
                return !$this->disabledServicesConfig->isTradingDisabled();
            default:
                return false;
        }
    }
}
