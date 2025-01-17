<?php declare(strict_types = 1);

namespace App\Utils;

/** @codeCoverageIgnore */
class DateTime implements DateTimeInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
