<?php

namespace App\Exchange\Trade\Config;

class PrelaunchConfig
{
    /** @var string */
    public $startDate;

    /** @var string */
    public $finishDate;

    /** @var float */
    public $referralFee;

    public function __construct(
        string $startDate,
        string $finishDate,
        float $referralFee
    )
    {
        $this->startDate = $startDate;
        $this->finishDate = $finishDate;
        $this->referralFee = $referralFee;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->startDate);
    }

    public function getFinishDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->finishDate);
    }

    public function getReferralFee(): float
    {
        return $this->referralFee;
    }
}