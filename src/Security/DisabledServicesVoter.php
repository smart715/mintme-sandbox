<?php declare(strict_types = 1);

namespace App\Security;

use App\Security\Config\DisabledServicesConfig;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DisabledServicesVoter extends Voter
{
    private const DEPOSIT = 'deposit';
    private const WITHDRAW = 'withdraw';
    private const DEPLOY = 'deploy';
    private const NEW_TRADES = 'new-trades';
    private const TRADING = 'trading';

    private const ALL_ACTIONS = [
        self::DEPOSIT,
        self::WITHDRAW,
        self::DEPLOY,
        self::NEW_TRADES,
        self::TRADING,
    ];

    /** @var DisabledServicesConfig */
    private $disbledServicesConfig;

    public function __construct(DisabledServicesConfig $disabledServicesConfig)
    {
        $this->disbledServicesConfig = $disabledServicesConfig;
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
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if ($this->disbledServicesConfig->isAllServicesDisabled()) {
            return false;
        }

        switch ($attribute) {
            case self::DEPOSIT:
                return !$this->disbledServicesConfig->isDepositDisabled();
            case self::WITHDRAW:
                return !$this->disbledServicesConfig->isWithdrawalsDisabled();
            case self::DEPLOY:
                return !$this->disbledServicesConfig->isDeployDisabled();
            case self::NEW_TRADES:
                return !$this->disbledServicesConfig->isNewTradesDisabled();
            case self::TRADING:
                return !$this->disbledServicesConfig->isTradingDisabled();
        }

        return false;
    }
}
