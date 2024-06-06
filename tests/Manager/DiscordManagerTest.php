<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\DiscordConfig;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exception\Discord\MissingPermissionsException;
use App\Exception\Discord\UnknownRoleException;
use App\Manager\DiscordConfigManagerInterface;
use App\Manager\DiscordManager;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use App\Manager\UserTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Command\Exception\CommandClientException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RestCord\DiscordClient;
use RestCord\Interfaces\Guild;
use RestCord\Model\Permissions\Role;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DiscordManagerTest extends TestCase
{
    public function testCreateRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord('createGuildRole', [
            'guild.id' => 1,
            'name' => 'test',
            'color' => 0,
        ]);

        $dm = $this->createDiscordManager($discord);

        $dm->createRole($dr);
    }

    public function testCreateRoleMissingPermissions(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord(
            'createGuildRole',
            null,
            $this->mockException(DiscordManagerInterface::MISSING_PERMISSIONS_ERROR_CODE)
        );

        $dcm = $this->createMock(DiscordConfigManagerInterface::class);
        $dcm->expects($this->once())->method('disable')->with($dr->getToken()->getDiscordConfig());

        $dm = $this->createDiscordManager($discord, null, null, null, $dcm);

        $this->expectException(MissingPermissionsException::class);

        $dm->createRole($dr);
    }

    public function testUpdateRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $with = [
            'guild.id' => 1,
            'role.id' => 1,
            'name' => 'test',
            'color' => 0,
        ];

        $dm = $this->createDiscordManager(
            $this->mockDiscord('modifyGuildRole', $with)
        );

        $dm->updateRole($dr);
    }

    public function testUpdateRoleMissingPermissions(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord(
            'modifyGuildRole',
            null,
            $this->mockException(DiscordManagerInterface::MISSING_PERMISSIONS_ERROR_CODE)
        );

        $dcm = $this->createMock(DiscordConfigManagerInterface::class);
        $dcm->expects($this->once())->method('disable')->with($dr->getToken()->getDiscordConfig());

        $dm = $this->createDiscordManager($discord, null, null, null, $dcm);

        $this->expectException(MissingPermissionsException::class);

        $dm->updateRole($dr);
    }

    public function testUpdateRoleUnknownRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord(
            'modifyGuildRole',
            null,
            $this->mockException(DiscordManagerInterface::UNKNOWN_ROLE_ERROR_CODE)
        );

        $drm = $this->createMock(DiscordRoleManagerInterface::class);
        $drm->expects($this->once())->method('removeRole')->with($dr);

        $dm = $this->createDiscordManager($discord, null, null, $drm);

        $this->expectException(UnknownRoleException::class);

        $dm->updateRole($dr);
    }

    public function testDeleteRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $with = [
            'guild.id' => 1,
            'role.id' => 1,
        ];

        $dm = $this->createDiscordManager(
            $this->mockDiscord('deleteGuildRole', $with)
        );

        $dm->deleteRole($dr);
    }

    public function testDeleteRoleMissingPermissions(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord(
            'deleteGuildRole',
            null,
            $this->mockException(DiscordManagerInterface::MISSING_PERMISSIONS_ERROR_CODE)
        );

        $dcm = $this->createMock(DiscordConfigManagerInterface::class);
        $dcm->expects($this->once())->method('disable')->with($dr->getToken()->getDiscordConfig());

        $dm = $this->createDiscordManager($discord, null, null, null, $dcm);

        $this->expectException(MissingPermissionsException::class);

        $dm->deleteRole($dr);
    }

    public function testDeleteRoleUnknownRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);

        $discord = $this->mockDiscord(
            'deleteGuildRole',
            null,
            $this->mockException(DiscordManagerInterface::UNKNOWN_ROLE_ERROR_CODE)
        );

        $drm = $this->createMock(DiscordRoleManagerInterface::class);
        $drm->expects($this->once())->method('removeRole')->with($dr);

        $dm = $this->createDiscordManager($discord, null, null, $drm);

        $this->expectException(UnknownRoleException::class);

        $dm->deleteRole($dr);
    }

    public function testAddGuildMemberRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $with = [
            'guild.id' => 1,
            'role.id' => 1,
            'user.id' => 1,
        ];

        $dm = $this->createDiscordManager(
            $this->mockDiscord('addGuildMemberRole', $with)
        );

        $dm->addGuildMemberRole($u, $dr);
    }

    public function testAddGuildMemberRoleMissingPermissions(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $discord = $this->mockDiscord(
            'addGuildMemberRole',
            null,
            $this->mockException(DiscordManagerInterface::MISSING_ACCESS_ERROR_CODE)
        );

        $dcm = $this->createMock(DiscordConfigManagerInterface::class);
        $dcm->expects($this->once())->method('disable')->with($dr->getToken()->getDiscordConfig());

        $dm = $this->createDiscordManager($discord, null, null, null, $dcm);

        $this->expectException(MissingPermissionsException::class);

        $dm->addGuildMemberRole($u, $dr);
    }

    public function testAddGuildMemberRoleUnknownRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $discord = $this->mockDiscord(
            'addGuildMemberRole',
            null,
            $this->mockException(DiscordManagerInterface::UNKNOWN_ROLE_ERROR_CODE)
        );

        $drm = $this->createMock(DiscordRoleManagerInterface::class);
        $drm->expects($this->once())->method('removeRole')->with($dr);

        $dm = $this->createDiscordManager($discord, null, null, $drm);

        $this->expectException(UnknownRoleException::class);

        $dm->addGuildMemberRole($u, $dr);
    }

    public function testRemoveGuildMemberRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $with = [
            'guild.id' => 1,
            'role.id' => 1,
            'user.id' => 1,
        ];

        $dm = $this->createDiscordManager(
            $this->mockDiscord('removeGuildMemberRole', $with)
        );

        $dm->removeGuildMemberRole($u, $dr);
    }

    public function testRemoveGuildMemberRoleMissingPermissions(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $discord = $this->mockDiscord(
            'removeGuildMemberRole',
            null,
            $this->mockException(DiscordManagerInterface::MISSING_ACCESS_ERROR_CODE)
        );

        $dcm = $this->createMock(DiscordConfigManagerInterface::class);
        $dcm->expects($this->once())->method('disable')->with($dr->getToken()->getDiscordConfig());

        $dm = $this->createDiscordManager($discord, null, null, null, $dcm);

        $this->expectException(MissingPermissionsException::class);

        $dm->removeGuildMemberRole($u, $dr);
    }

    public function testRemoveGuildMemberRoleUnknownRole(): void
    {
        $dr = $this->mockDiscordRole('test', 0, 1, 1);
        $u = $this->mockUser(1);

        $discord = $this->mockDiscord(
            'removeGuildMemberRole',
            null,
            $this->mockException(DiscordManagerInterface::UNKNOWN_ROLE_ERROR_CODE)
        );

        $drm = $this->createMock(DiscordRoleManagerInterface::class);
        $drm->expects($this->once())->method('removeRole')->with($dr);

        $dm = $this->createDiscordManager($discord, null, null, $drm);

        $this->expectException(UnknownRoleException::class);

        $dm->removeGuildMemberRole($u, $dr);
    }

    public function testLeaveGuild(): void
    {
        $t = $this->mockToken(1);

        $discord = $this->createMock(DiscordClient::class);
        $discord->user = $this->createMock(\RestCord\Interfaces\User::class);
        $discord->user->expects($this->once())->method('leaveGuild')->with([
            'guild.id' => 1,
        ]);

        $dm = $this->createDiscordManager(null, $discord);

        $dm->leaveGuild($t);
    }

    public function testGetGuild(): void
    {
        $guild = $this->createMock(\RestCord\Model\Guild\Guild::class);
        $t = $this->mockToken(1);

        $discord = $this->mockDiscord(
            'getGuild',
            ['guild.id' => 1],
            null,
            $guild
        );

        $dm = $this->createDiscordManager($discord);

        $this->assertEquals($guild, $dm->getGuild($t));
    }

    public function testGetManageableRoles(): void
    {
        $guild = new \RestCord\Model\Guild\Guild();

        $botRole = (object) [
            'position' => 1,
            'tags' => (object) [
                'bot_id' => 'testClientId',
            ],
        ];

        $defaultRole = (object) [
            'name' => '@everyone',
            'position' => 0,
        ];

        $manageableRole = (object) [
            'name' => 'foo',
            'color' => 0,
            'position' => 0,
            'id' => '1',
        ];

        $nonManageableRole = (object) [
            'name' => 'baz',
            'color' => 0,
            'position' => 2,
        ];

        $guild->roles = [$botRole, $defaultRole, $manageableRole, $nonManageableRole];

        $dm = $this->createDiscordManager();

        $manageableRoles = $dm->getManageableRoles($guild);

        $this->assertEquals(1, count($manageableRoles));
        $this->assertEquals('foo', $manageableRoles['1']->getName());
    }

    public function testRemoveAllGuildMembersRole(): void
    {
        $token = $this->mockToken(1);
        $role = $this->mockDiscordRole('testRole', 0, 1, 1);
        $userTokenManagerMock = $this->createMock(UserTokenManagerInterface::class);

        $userTokenManagerMock->method('getHoldersWithDiscord')->willReturn([
            (new UserToken())->setToken($token)->setUser($this->mockUser(1)),
            (new UserToken())->setToken($token)->setUser($this->mockUser(2)),
        ]);

        $discord = $this->createMock(DiscordClient::class);
        $discord->guild = $this->createMock(\RestCord\Interfaces\Guild::class);
        $discord->guild
            ->expects($this->exactly(2))
            ->method('removeGuildMemberRole')
            ->withConsecutive([[
                'guild.id' => 1,
                'role.id' => 1,
                'user.id' => 1,
            ]], [[
                'guild.id' => 1,
                'role.id' => 1,
                'user.id' => 2,
            ]]);

        $dm = $this->createDiscordManager($discord, null, null, null, null, null, $userTokenManagerMock);

        $dm->removeAllGuildMembersRole($token, $role);
    }

    public function testUpdateRolesOfUsers(): void
    {
        $token = $this->mockToken(1);
        $userTokenManagerMock = $this->createMock(UserTokenManagerInterface::class);

        $userTokenManagerMock->method('getHoldersWithDiscord')->willReturn([
            (new UserToken())->setToken($token)->setUser($this->mockUser(1)),
            (new UserToken())->setToken($token)->setUser($this->mockUser(2)),
        ]);

        $dm = $this->getMockBuilder(DiscordManager::class)
            ->setConstructorArgs([
                $this->createMock(DiscordClient::class),
                $this->createMock(DiscordClient::class),
                $this->createMock(LoggerInterface::class),
                $this->createMock(DiscordRoleManagerInterface::class),
                $this->createMock(DiscordConfigManagerInterface::class),
                $this->createMock(EntityManagerInterface::class),
                $userTokenManagerMock,
                $this->createMock(EventDispatcherInterface::class),
                '',
                '',
            ])
            ->onlyMethods(['updateRoleOfUser'])
            ->getMock();

        $dm->expects($this->exactly(2))->method('updateRoleOfUser')->with(
            $this->anything(),
            $token,
            false,
            true,
        );

        $dm->updateRolesOfUsers($token);
    }

    /**
     * @param mixed $returnValue
     */
    private function mockDiscord(
        string $method,
        ?array $with = null,
        ?CommandClientException $e = null,
        $returnValue = null
    ): DiscordClient {
        $role = $this->createMock(Role::class);
        $role->id = 1;

        $guild = $this->createMock(Guild::class);
        $m = $guild->expects($this->once())->method($method);

        if ($with) {
            if ($returnValue) {
                $m->with($with)->willReturn($returnValue);
            } else {
                $m->with($with)->willReturn($role);
            }
        } elseif ($e) {
            $m->willThrowException($e);
        }

        $discord = $this->createMock(DiscordClient::class);
        $discord->guild = $guild;

        return $discord;
    }

    private function mockToken(int $guildId): Token
    {
        $dc = $this->createMock(DiscordConfig::class);
        $dc->method('getGuildId')->willReturn($guildId);

        $t = $this->createMock(Token::class);
        $t->method('getDiscordConfig')->willReturn($dc);

        return $t;
    }

    private function mockDiscordRole(string $name, int $color, int $discordRoleId, int $guildId): DiscordRole
    {
        $t = $this->mockToken($guildId);

        $dr = $this->createMock(DiscordRole::class);
        $dr->method('getName')->willReturn($name);
        $dr->method('getColor')->willReturn($color);
        $dr->method('getToken')->willReturn($t);
        $dr->method('getDiscordId')->willReturn($discordRoleId);

        return $dr;
    }

    private function mockException(int $code): CommandClientException
    {
        /** @var string $content */
        $content = json_encode(['code' => $code]);

        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);

        $e = $this->createMock(CommandClientException::class);
        $e->method('getResponse')->willReturn($response);

        return $e;
    }

    private function mockUser(int $discordId): User
    {
        $u = $this->createMock(User::class);
        $u->method('getDiscordId')->willReturn($discordId);

        return $u;
    }

    private function createDiscordManager(
        ?DiscordClient $discord = null,
        ?DiscordClient $discordForLeaveGuild = null,
        ?LoggerInterface $logger = null,
        ?DiscordRoleManagerInterface $drm = null,
        ?DiscordConfigManagerInterface $dcm = null,
        ?EntityManagerInterface $em = null,
        ?UserTokenManagerInterface $userTokenManager = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $publicKey = 'testPublicKey',
        string $clientId = 'testClientId'
    ): DiscordManagerInterface {
        return new DiscordManager(
            $discord ?? $this->createMock(DiscordClient::class),
            $discordForLeaveGuild ?? $this->createMock(DiscordClient::class),
            $logger ?? $this->createMock(LoggerInterface::class),
            $drm ?? $this->createMock(DiscordRoleManagerInterface::class),
            $dcm ?? $this->createMock(DiscordConfigManagerInterface::class),
            $em ?? $this->createMock(EntityManagerInterface::class),
            $userTokenManager ?? $this->createMock(UserTokenManagerInterface::class),
            $eventDispatcher ??$this->createMock(EventDispatcherInterface::class),
            $publicKey,
            $clientId
        );
    }
}
