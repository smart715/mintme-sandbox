<?php declare(strict_types = 1);

namespace App\Discord\SlashCommands;

use App\Manager\DiscordConfigManagerInterface;
use App\Manager\DiscordManagerInterface;
use App\Manager\UserManagerInterface;
use Discord\InteractionResponseType;

class GetRoleCommand implements SlashCommandInterface
{
    private const NAME = 'getrole';

    private UserManagerInterface $userManager;
    private DiscordConfigManagerInterface $discordConfigManager;
    private DiscordManagerInterface $discordManager;

    public function __construct(
        UserManagerInterface $userManager,
        DiscordConfigManagerInterface $discordConfigManager,
        DiscordManagerInterface $discordManager
    ) {
        $this->userManager = $userManager;
        $this->discordConfigManager = $discordConfigManager;
        $this->discordManager = $discordManager;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function handleInteraction(array $params): array
    {
        $guildId = (int)$params['guild_id'];
        $userDiscordId = (int)$params['member']['user']['id'];

        $user = $this->userManager->findByDiscordId($userDiscordId);
        $discordConfig = $this->discordConfigManager->findByGuildId($guildId);

        if (!$user || !$discordConfig) {
            return [
                'type' => InteractionResponseType::CHANNEL_MESSAGE_WITH_SOURCE,
                'data' => [
                    'content' => 'unknown user or token',
                ],
            ];
        }

        $this->discordManager->updateRoleOfUser($user, $discordConfig->getToken(), true);

        return [
            'type' => InteractionResponseType::CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => [
                'content' => 'role given',
            ],
        ];
    }
}
