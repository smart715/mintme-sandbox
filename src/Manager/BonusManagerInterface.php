<?php declare(strict_types = 1);

namespace App\Manager;

interface BonusManagerInterface
{
    public function isLimitReached(string $limit, string $type): bool;
}
