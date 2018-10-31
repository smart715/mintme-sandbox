<?php

namespace App\Manager;

use App\Entity\User;

interface UserManagerInterface
{
    public function findByEmail(string $email): ?User;

    public function findByIds(array $userIds): array;
}
