<?php declare(strict_types = 1);

namespace App\Discord\SlashCommands;

interface SlashCommandInterface
{
    public static function getName(): string;

    public function handleInteraction(array $params): array;
}
