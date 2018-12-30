<?php

namespace App\Utils;

class DateTime implements DateTimeInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
