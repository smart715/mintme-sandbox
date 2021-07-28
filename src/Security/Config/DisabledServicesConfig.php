<?php declare(strict_types = 1);

namespace App\Security\Config;

use Symfony\Component\Serializer\Annotation\Groups;

class DisabledServicesConfig
{
    private bool $depositDisabled;

    private bool $withdrawalsDisabled;

    private bool $tokenDepositDisabled;

    private bool $tokenWithdrawDisabled;

    private bool $deployDisabled;

    private bool $newTradesDisabled;

    private bool $tradingDisabled;

    private bool $allServicesDisabled;

    public function __construct(
        bool $depositDisabled,
        bool $withdrawalsDisabled,
        bool $tokenDepositDisabled,
        bool $tokenWithdrawDisabled,
        bool $deployDisabled,
        bool $newTradesDisabled,
        bool $tradingDisabled,
        bool $allServicesDisabled
    ) {
        $this->depositDisabled = $depositDisabled;
        $this->withdrawalsDisabled = $withdrawalsDisabled;
        $this->tokenDepositDisabled = $tokenDepositDisabled;
        $this->tokenWithdrawDisabled = $tokenWithdrawDisabled;
        $this->deployDisabled = $deployDisabled;
        $this->newTradesDisabled = $newTradesDisabled;
        $this->tradingDisabled = $tradingDisabled;
        $this->allServicesDisabled = $allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isDepositDisabled(): bool
    {
        return $this->depositDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isWithdrawalsDisabled(): bool
    {
        return $this->withdrawalsDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTokenDepositsDisabled(): bool
    {
        return $this->tokenDepositDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTokenWithdrawalsDisabled(): bool
    {
        return $this->tokenWithdrawDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isDeployDisabled(): bool
    {
        return $this->deployDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isNewTradesDisabled(): bool
    {
        return $this->newTradesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTradingDisabled(): bool
    {
        return $this->tradingDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isAllServicesDisabled(): bool
    {
        return $this->allServicesDisabled;
    }
}
