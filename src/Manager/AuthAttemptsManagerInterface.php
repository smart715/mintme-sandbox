<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface AuthAttemptsManagerInterface
{
    public function initChances(User $user): void;
    public function decrementChances(User $user): int;
    public function canDecrementChances(User $user): bool;
    public function getWaitedHours(User $user): int;
    public function getMustWaitHours(User $user): int;
}
