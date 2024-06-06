<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface OrderManagerInterface
{
    public function deleteOrdersByUser(User $user): void;
}
