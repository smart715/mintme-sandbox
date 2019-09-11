<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface BonusManagerInterface
{
    public function isLimitReached(int $limit): bool;
}
