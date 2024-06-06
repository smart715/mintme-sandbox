<?php declare(strict_types = 1);

namespace App\Events\Activity;

interface ActivityEventInterface
{
    public function getType(): int;
}
