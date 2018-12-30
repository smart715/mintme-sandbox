<?php

namespace App\Utils;

interface DateTimeInterface
{
    public function now(): \DateTimeImmutable;
}
