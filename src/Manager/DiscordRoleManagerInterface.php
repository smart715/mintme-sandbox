<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;

interface DiscordRoleManagerInterface
{

    public function findRoleOfUser(User $user, Token $token): ?DiscordRole;

    public function removeRole(DiscordRole $role, bool $andFlush = true): void;

    public function removeAllRoles(Token $token): void;

    /**
     * @param DiscordRole[] $roles
     */
    public function removeRoles(array $roles, bool $andFlush = true): void;
}
