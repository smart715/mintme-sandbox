<?php declare(strict_types = 1);

namespace App\Manager;

use App\Activity\ActivityTypes;
use App\Entity\DiscordRole;
use App\Entity\DiscordRoleUser;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\UserTokenEventActivity;
use App\Exception\Discord\DiscordException;
use App\Exception\Discord\MissingPermissionsException;
use App\Exception\Discord\UnknownRoleException;
use Discord\Interaction;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Command\Exception\CommandClientException;
use Psr\Log\LoggerInterface;
use RestCord\DiscordClient;
use RestCord\Model\Guild\Guild;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DiscordManager implements DiscordManagerInterface
{
    private const DEFAULT_ROLE = '@everyone';

    private DiscordClient $discord;
    // for some reason the leaveGuild request fails when the Content-Type: application/json header is present
    // and it's present by default on the requests made by RestCord, but it also is needed for the other requests,
    // so this method needs its own custom DiscordClient without that header
    private DiscordClient $discordForLeaveGuild;
    private LoggerInterface $logger;
    private DiscordRoleManagerInterface $discordRoleManager;
    private DiscordConfigManagerInterface $discordConfigManager;
    private EntityManagerInterface $entityManager;
    private UserTokenManagerInterface $userTokenManager;
    private EventDispatcherInterface $eventDispatcher;
    private string $publicKey;
    private string $clientId;

    public function __construct(
        DiscordClient $discord,
        DiscordClient $discordForLeaveGuild,
        LoggerInterface $logger,
        DiscordRoleManagerInterface $discordRoleManager,
        DiscordConfigManagerInterface $discordConfigManager,
        EntityManagerInterface $entityManager,
        UserTokenManagerInterface $userTokenManager,
        EventDispatcherInterface $eventDispatcher,
        string $publicKey,
        string $clientId
    ) {
        $this->discord = $discord;
        $this->discordForLeaveGuild = $discordForLeaveGuild;
        $this->logger = $logger;
        $this->discordRoleManager = $discordRoleManager;
        $this->discordConfigManager = $discordConfigManager;
        $this->entityManager = $entityManager;
        $this->userTokenManager = $userTokenManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->publicKey = $publicKey;
        $this->clientId = $clientId;
    }

    public function createRoles(array $roles): void
    {
        foreach ($roles as $role) {
            $this->createRole($role);
        }
    }

    public function createRole(DiscordRole $role): void
    {
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();

        try {
            $roleData = $this->discord->guild->createGuildRole([
                'guild.id' => $guildId,
                'name' => $role->getName(),
                'color' => $role->getColor(),
            ]);

            $role->setDiscordId($roleData->id);
        } catch (CommandClientException $e) {
            $this->errorHandler($e, $role);
        }
    }

    public function updateRoles(array $roles): void
    {
        foreach ($roles as $role) {
            try {
                $this->updateRole($role);
            } catch (UnknownRoleException $e) {
                continue;
            }
        }
    }

    public function updateRole(DiscordRole $role): void
    {
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();

        try {
            $this->discord->guild->modifyGuildRole([
                'guild.id' => $guildId,
                'role.id' => $role->getDiscordId(),
                'name' => $role->getName(),
                'color' => $role->getColor(),
            ]);
        } catch (CommandClientException $e) {
            $this->errorHandler($e, $role);
        }
    }

    public function deleteRoles(array $roles): void
    {
        foreach ($roles as $role) {
            try {
                $this->deleteRole($role);
            } catch (UnknownRoleException $e) {
                continue;
            }
        }
    }

    public function deleteRole(DiscordRole $role): void
    {
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();

        try {
            $this->discord->guild->deleteGuildRole([
                'guild.id' => $guildId,
                'role.id' => $role->getDiscordId(),
            ]);
        } catch (CommandClientException $e) {
            $this->errorHandler($e, $role);
        }
    }

    public function addGuildMemberRole(User $user, DiscordRole $role): void
    {
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();
        $roleDiscordId = $role->getDiscordId();
        $userDiscordId = $user->getDiscordId();

        try {
            $this->discord->guild->addGuildMemberRole([
                'guild.id' => $guildId,
                'role.id' => $roleDiscordId,
                'user.id' => $userDiscordId,
            ]);
        } catch (CommandClientException $e) {
            $this->errorHandler($e, $role);
        }
    }

    public function removeAllGuildMembersRole(Token $token, DiscordRole $role): void
    {
        $holders = $this->userTokenManager->getHoldersWithDiscord($token);
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();
        $roleDiscordId = $role->getDiscordId();

        foreach ($holders as $holder) {
            try {
                $this->discord->guild->removeGuildMemberRole([
                    'guild.id' => $guildId,
                    'role.id' => $roleDiscordId,
                    'user.id' => $holder->getUser()->getDiscordId(),
                ]);
            } catch (CommandClientException $e) {
                $this->errorHandler($e, $role);
            }
        }
    }

    public function removeGuildMemberRole(User $user, DiscordRole $role): void
    {
        $guildId = $role->getToken()->getDiscordConfig()->getGuildId();
        $roleDiscordId = $role->getDiscordId();
        $userDiscordId = $user->getDiscordId();

        try {
            $this->discord->guild->removeGuildMemberRole([
                'guild.id' => $guildId,
                'role.id' => $roleDiscordId,
                'user.id' => $userDiscordId,
            ]);
        } catch (CommandClientException $e) {
            $this->errorHandler($e, $role);
        }
    }

    public function verifyInteraction(string $body, string $signature, string $timestamp): bool
    {
        return Interaction::verifyKey($body, $signature, $timestamp, $this->publicKey);
    }

    public function leaveGuild(Token $token): void
    {
        $guildId = $token->getDiscordConfig()->getGuildId();

        if (!$guildId) {
            return;
        }

        $this->discordForLeaveGuild->user->leaveGuild(['guild.id' => $guildId]);
    }

    public function getGuild(Token $token): Guild
    {
        $guildId = $token->getDiscordConfig()->getGuildId();

        return $this->discord->guild->getGuild(['guild.id' => $guildId]);
    }

    public function getManageableRoles(Guild $guild): array
    {
        $roles = $guild->roles;

        $botRole = array_filter(
            $roles,
            fn ($role) => property_exists($role, 'tags') && $this->clientId === $role->tags->bot_id
        );

        $botRole = array_pop($botRole);

        // the roles with lower position than our bot's role are the ones our bot can manage
        $filteredRoles = array_filter(
            $roles,
            fn ($role) => $role->position < $botRole->position && self::DEFAULT_ROLE !== $role->name
        );

        $result = [];

        foreach ($filteredRoles as $role) {
            $result[$role->id] = (new DiscordRole())
                ->setName($role->name)
                ->setColor($role->color)
                ->setDiscordId((int)$role->id)
            ;
        }

        return $result;
    }

    public function updateRolesOfUsers(Token $token): void
    {
        $holders = $this->userTokenManager->getHoldersWithDiscord($token);

        foreach ($holders as $holder) {
            $this->updateRoleOfUser($holder->getUser(), $token, false, true);
        }

        $this->entityManager->flush();
    }

    public function updateRoleOfUser(
        User $user,
        Token $token,
        bool $updateOnDiscordIfSame = false,
        bool $dontFlush = false
    ): void {
        if (!$token->getDiscordConfig()->getEnabled()
            || !$token->getDiscordConfig()->getSpecialRolesEnabled()
            || !$user->isSignedInWithDiscord()
            || $token->isOwner($user->getProfile()->getTokens())
        ) {
            return;
        }

        $this->entityManager->refresh($user);

        $dru = $user->getDiscordRoleUser($token);

        $currentRole = $dru
            ? $dru->getDiscordRole()
            : null;

        $newRole = $this->discordRoleManager->findRoleOfUser($user, $token);

        if ($currentRole === $newRole) {
            if ($updateOnDiscordIfSame) {
                try {
                    $this->addGuildMemberRole($user, $currentRole);
                } catch (\Throwable $e) {
                    return;
                }
            }

            return;
        }

        if ($currentRole) {
            try {
                $this->removeGuildMemberRole($user, $currentRole);
            } catch (\Throwable $e) {
                return;
            }
        }

        if ($newRole) {
            try {
                $this->addGuildMemberRole($user, $newRole);
            } catch (\Throwable $e) {
                return;
            }

            $dru = $dru
                ? $dru->setDiscordRole($newRole)
                : (new DiscordRoleUser())->setDiscordRole($newRole)->setUser($user);

            $this->entityManager->persist($dru);
        } else {
            $this->entityManager->remove($dru);
        }

        $this->eventDispatcher->dispatch(
            new UserTokenEventActivity($user, $token, ActivityTypes::DISCORD_REWARD_RECEIVED),
            UserTokenEventActivity::NAME
        );

        if (!$dontFlush) {
            $this->entityManager->flush();
        }
    }

    private function getError(CommandClientException $e): array
    {
        return \json_decode($e->getResponse()->getBody()->getContents(), true);
    }

    /**
     * @throws UnknownRoleException
     * @throws MissingPermissionsException
     * @throws DiscordException
     */
    private function errorHandler(CommandClientException $e, DiscordRole $role): void
    {
        $error = $this->getError($e);

        switch ($error['code']) {
            case self::UNKNOWN_ROLE_ERROR_CODE:
                $this->discordRoleManager->removeRole($role);

                $this->logger->error(
                    "Remove role {$role->getName()} of {$role->getToken()->getName()} because of unknown role error",
                    $error
                );

                throw new UnknownRoleException($error, $e);
            case self::MISSING_PERMISSIONS_ERROR_CODE:
            case self::MISSING_ACCESS_ERROR_CODE:
                $this->discordConfigManager->disable($role->getToken()->getDiscordConfig());

                $this->logger->error(
                    "Disabled discord rewards for {$role->getToken()->getName()} because of missing permissions error",
                    $error
                );

                throw new MissingPermissionsException($error, $e);
            default:
                $this->logger->error(
                    "Unknown discord error for role {$role->getName()} of {$role->getToken()->getName()}",
                    $error
                );

                throw new DiscordException($error, $e);
        }
    }
}
