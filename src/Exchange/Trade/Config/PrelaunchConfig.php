<?php declare(strict_types = 1);

namespace App\Exchange\Trade\Config;

use App\Utils\DateTime;
use App\Utils\DateTimeInterface;
use DateTimeImmutable;

class PrelaunchConfig
{
    /** @var string */
    private $finishDate;

    /** @var float */
    private $referralFee;

    /** @var bool */
    private $autostart;

    /** @var DateTimeInterface */
    private $dateTime;

    public function __construct(
        string $finishDate,
        float $referralFee,
        bool $autostart,
        DateTimeInterface $dateTime
    ) {
        $this->finishDate = $finishDate;
        $this->referralFee = $referralFee;
        $this->autostart = $autostart;
        $this->dateTime = $dateTime;
    }

    public function getFinishDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->finishDate) ?: $this->dateTime->now();
    }

    public function isEnabled(): bool
    {
        return $this->getFinishDate()->getTimestamp() > $this->dateTime->now()->getTimestamp();
    }

    public function isFinished(): bool
    {
        return $this->autostart && !$this->isEnabled();
    }

    /** @codeCoverageIgnore */
    public function getReferralFee(): float
    {
        return $this->referralFee;
    }
}
