<?php declare(strict_types = 1);

namespace App\Utils;

interface DateTimeInterface
{
    public function now(): \DateTimeImmutable;
}
