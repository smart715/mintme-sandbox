<?php declare(strict_types = 1);

namespace App\Tests\Discord\SlashCommands;

use App\Discord\SlashCommands\GetRoleCommand;
use App\Entity\Token\DiscordConfig;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\DiscordConfigManagerInterface;
use App\Manager\DiscordManagerInterface;
use App\Manager\UserManagerInterface;
use PHPUnit\Framework\TestCase;

class GetRoleCommandTest extends TestCase
{
    private const PARAMS = [
        'guild_id' => '123',
        'member' => ['user' => ['id' => '456']],
    ];

    public function testHandleInteraction(): void
    {
        $command = new GetRoleCommand(
            $this->mockUserManager($this->mockUser()),
            $this->mockDiscordConfigManager($this->mockDiscordConfig()),
            $this->mockDiscordManager(),
        );


        $this->assertEquals(
            [
                'type' => 4,
                'data' => ['content' => 'role given'],
            ],
            $command->handleInteraction(self::PARAMS)
        );
    }

    public function testHandleInteractionWithNoUser(): void
    {
        $command = new GetRoleCommand(
            $this->mockUserManager(),
            $this->mockDiscordConfigManager($this->mockDiscordConfig()),
            $this->mockDiscordManager(false),
        );

        $this->assertEquals(
            [
                'type' => 4,
                'data' => ['content' => 'unknown user or token'],
            ],
            $command->handleInteraction(self::PARAMS)
        );
    }

    public function testHandleInteractionWithNoDiscordConfig(): void
    {
        $command = new GetRoleCommand(
            $this->mockUserManager($this->mockUser()),
            $this->mockDiscordConfigManager(),
            $this->mockDiscordManager(false),
        );

        $this->assertEquals(
            [
                'type' => 4,
                'data' => ['content' => 'unknown user or token'],
            ],
            $command->handleInteraction(self::PARAMS)
        );
    }

    public function testGetName(): void
    {
        $command = new GetRoleCommand(
            $this->createMock(UserManagerInterface::class),
            $this->createMock(DiscordConfigManagerInterface::class),
            $this->createMock(DiscordManagerInterface::class),
        );

        $this->assertEquals('getrole', $command->getName());
    }

    private function mockDiscordManager(bool $isSuccessful = true): DiscordManagerInterface
    {
        $discordManager = $this->createMock(DiscordManagerInterface::class);
        $discordManager->expects($isSuccessful ? $this->once() : $this->never())
            ->method('updateRoleOfUser');

        return $discordManager;
    }

    private function mockDiscordConfigManager(?DiscordConfig $discordConfig = null): DiscordConfigManagerInterface
    {
        $discordConfigManager = $this->createMock(DiscordConfigManagerInterface::class);
        $discordConfigManager->expects($this->once())
            ->method('findByGuildId')
            ->willReturn($discordConfig);

        return $discordConfigManager;
    }

    private function mockUserManager(?User $user = null): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->expects($this->once())
            ->method('findByDiscordId')
            ->willReturn($user);

        return $userManager;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockDiscordConfig(): DiscordConfig
    {
        $discordConfig = $this->createMock(DiscordConfig::class);
        $discordConfig->method('getToken')->willReturn($this->mockToken());

        return $discordConfig;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
