<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;

interface DiscordRoleManagerInterface
{

    public function findRoleOfUser(User $user, Token $token): ?DiscordRole;

    public function removeRole(DiscordRole $role): void;

    public function removeRoles(Token $token): void;
}
