<?php declare(strict_types = 1);

namespace App\Utils;

class Clock implements ClockInterface
{
    public function sleep(int $s): void
    {
        sleep($s);
    }
}
