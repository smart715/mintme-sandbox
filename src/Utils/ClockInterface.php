<?php declare(strict_types = 1);

namespace App\Utils;

interface ClockInterface
{
    public function sleep(int $s): void;
}
