<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\DiscordConfig;

interface DiscordConfigManagerInterface
{
    public function disable(DiscordConfig $config): void;
    public function findByGuildId(int $guildId): ?DiscordConfig;
}
