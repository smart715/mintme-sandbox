<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use App\Entity\PhoneNumber;
use App\Entity\User;
use DateTimeImmutable;

interface ValidationCodeInterface
{
    public function getCode(): ?string;

    public function setCode(?string $code): self;

    public function getCodeType(): string;

    public function setCodeType(?string $codeType): self;

    public function getFailedAttempts(): int;

    public function incrementFailedAttempts(): self;

    public function setFailedAttempts(int $failedAttempts): self;

    public function getDailyAttempts(): int;

    public function setDailyAttempts(int $dailyAttempts): self;

    public function getWeeklyAttempts(): int;

    public function setWeeklyAttempts(int $weeklyAttempts): self;

    public function getMonthlyAttempts(): int;

    public function setMonthlyAttempts(int $monthlyAttempts): self;

    public function getTotalAttempts(): int;

    public function setTotalAttempts(int $totalAttempts): self;

    public function getAttemptsDate(): ?DateTimeImmutable;

    public function setAttemptsDate(?DateTimeImmutable $attemptsDate = null): self;

    public function getSendDate(): ?DateTimeImmutable;

    public function setSendDate(?DateTimeImmutable $sendDate): self;

    public function getPhoneNumber(): ?PhoneNumber;

    public function setPhoneNumber(PhoneNumber $phoneNumber): self;

    public function getOwner(): ?ValidationCodeOwnerInterface;

    public function getUser(): ?User;
    
    public function shouldBlockOnLimitReached(): bool;
}
