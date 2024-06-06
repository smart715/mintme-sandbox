<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserAction;
use App\Repository\UserActionRepository;

interface UserActionManagerInterface
{
    public function getRepository(): UserActionRepository;
    public function getById(int $id): ?UserAction;
    public function getCountByUserAtDate(User $user, string $action, \DateTimeImmutable $date): int;
    public function createUserAction(User $user, string $action): void;
}
