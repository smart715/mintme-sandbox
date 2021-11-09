<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\User;

interface UserEventInterface
{
    public function getUser(): User;
}
