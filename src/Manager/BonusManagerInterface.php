<?php declare(strict_types = 1);

namespace App\Manager;

interface BonusManagerInterface
{
    public function isLimitReached(int $limit): bool;
}
