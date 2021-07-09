<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\Discord\DiscordException;
use App\Exception\Discord\MissingPermissionsException;
use App\Exception\Discord\UnknownRoleException;

interface DiscordManagerInterface
{
    public const UNKNOWN_ROLE_ERROR_CODE = 10011;
    public const MISSING_ACCESS_ERROR_CODE = 50001;
    public const MISSING_PERMISSIONS_ERROR_CODE = 50013;

    /**
     * @param DiscordRole[] $roles
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function createRoles(array $roles): void;

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function createRole(DiscordRole $role): void;

    /**
     * @param DiscordRole[] $roles
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function updateRoles(array $roles): void;

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function updateRole(DiscordRole $role): void;

    /**
     * @param DiscordRole[] $roles
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function deleteRoles(array $roles): void;

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function deleteRole(DiscordRole $role): void;

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function addGuildMemberRole(User $user, DiscordRole $role): void;

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    public function removeGuildMemberRole(User $user, DiscordRole $role): void;

    public function verifyInteraction(string $body, string $signature, string $timestamp): bool;

    public function leaveGuild(Token $token): void;
}
