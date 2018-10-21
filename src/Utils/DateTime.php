<?php

namespace App\Utils;

class DateTime implements DateTimeInterface
{
    public function now(): \DateTime
    {
        return new \DateTime();
    }
}
