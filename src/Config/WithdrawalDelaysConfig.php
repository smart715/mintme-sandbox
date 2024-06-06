<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class WithdrawalDelaysConfig
{
    private int $withdrawAfterLoginTime;
    private int $withdrawAfterRegisterTime;
    private int $withdrawalDelay;
    private int $orderDelay;
    private int $withdrawAfterUserChangeTime;

    public function __construct(
        int $withdrawAfterLoginTime,
        int $withdrawAfterRegisterTime,
        int $withdrawalDelay,
        int $orderDelay,
        int $withdrawAfterUserChangeTime
    ) {
        $this->withdrawAfterLoginTime = $withdrawAfterLoginTime;
        $this->withdrawAfterRegisterTime = $withdrawAfterRegisterTime;
        $this->withdrawalDelay = $withdrawalDelay;
        $this->orderDelay = $orderDelay;
        $this->withdrawAfterUserChangeTime = $withdrawAfterUserChangeTime;
    }

    public function getWithdrawAfterLoginTime(): int
    {
        return $this->withdrawAfterLoginTime;
    }

    public function getWithdrawAfterRegisterTime(): int
    {
        return $this->withdrawAfterRegisterTime;
    }

    public function getWithdrawalDelay(): int
    {
        return $this->withdrawalDelay;
    }

    public function getOrderDelay(): int
    {
        return $this->orderDelay;
    }

    public function getWithdrawAfterUserChangeTime(): int
    {
        return $this->withdrawAfterUserChangeTime;
    }
}
