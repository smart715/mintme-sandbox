<?php

namespace App\Exchange\Trade\Config;

class PrelaunchConfig
{
    /** @var string */
    public $finishDate;

    /** @var string */
    public $tradePeriod;

    /** @var float */
    public $referralFee;

    public function __construct(
        string $finishDate,
        string $tradePeriod,
        float $referralFee
    ) {
        $this->finishDate = $finishDate;
        $this->tradePeriod = $tradePeriod;
        $this->referralFee = $referralFee;
    }

    public function getFinishDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->finishDate) ?: new \DateTimeImmutable();
    }

    public function isEnabled(): bool
    {
        return $this->getFinishDate()->getTimestamp() > (new \DateTimeImmutable())->getTimestamp();
    }

    public function getTradeFinishDate(): \DateTimeImmutable
    {
        return $this->getFinishDate()->add(new \DateInterval($this->tradePeriod));
    }

    public function getReferralFee(): float
    {
        return $this->referralFee;
    }
}
