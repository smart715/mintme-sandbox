<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\DiscordConfig;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\Discord\MissingPermissionsException;
use App\Exception\Discord\UnknownRoleException;
use App\Manager\DiscordConfigManagerInterface;
use App\Manager\DiscordManager;
use App\Manager\DiscordManagerInterface;
use App\Manager\DiscordRoleManagerInterface;
use GuzzleHttp\Command\Exception\CommandClientException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RestCord\DiscordClient;
use RestCord\Interfaces\Guild;
use RestCord\Model\Permissions\Role;

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $dcm,
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $this->mockDiscord('modifyGuildRole', $with),
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $dcm,
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $drm,
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $this->mockDiscord('deleteGuildRole', $with),
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $dcm,
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $drm,
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $this->mockDiscord('addGuildMemberRole', $with),
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $dcm,
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $drm,
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $this->mockDiscord('removeGuildMemberRole', $with),
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $dcm,
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $discord,
            $this->createMock(DiscordClient::class),
            $this->createMock(LoggerInterface::class),
            $drm,
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

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

        $dm = new DiscordManager(
            $this->createMock(DiscordClient::class),
            $discord,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DiscordRoleManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            'testPublicKey'
        );

        $dm->leaveGuild($t);
    }

    private function mockDiscord(
        string $method,
        ?array $with = null,
        ?CommandClientException $e = null
    ): DiscordClient {
        $role = $this->createMock(Role::class);
        $role->id = 1;

        $guild = $this->createMock(Guild::class);
        $m = $guild->expects($this->once())->method($method);

        if ($with) {
            $m->with($with)->willReturn($role);
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
}
