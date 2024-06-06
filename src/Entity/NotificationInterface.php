<?php declare(strict_types = 1);

namespace App\Entity;

interface NotificationInterface
{
    public function getViewed(): bool;

    public function setViewed(bool $viewed): self;

    public function getDate(): \DateTimeImmutable;

    public function getType(): string;
}
