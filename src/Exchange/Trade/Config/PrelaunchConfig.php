<?php declare(strict_types = 1);

namespace App\Exchange\Trade\Config;

class PrelaunchConfig
{
    /** @var string */
    private $finishDate;

    /** @var string */
    private $tradePeriod;

    /** @var float */
    private $referralFee;

    /** @var bool */
    private $autostart;

    public function __construct(
        string $finishDate,
        string $tradePeriod,
        float $referralFee,
        bool $autostart
    ) {
        $this->finishDate = $finishDate;
        $this->tradePeriod = $tradePeriod;
        $this->referralFee = $referralFee;
        $this->autostart = $autostart;
    }

    public function getFinishDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->finishDate) ?: new \DateTimeImmutable();
    }

    public function isEnabled(): bool
    {
        return $this->getFinishDate()->getTimestamp() > (new \DateTimeImmutable())->getTimestamp();
    }

    public function isFinished(): bool
    {
        return $this->autostart && !$this->isEnabled();
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
