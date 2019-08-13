<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface EmailAuthManagerInterface
{
    public function checkCode(User $user, string $code): array;
    public function generateCode(User $user, int $expirationTime): string;
}
