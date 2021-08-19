<?php declare(strict_types = 1);

namespace App\Discord;

interface SlashCommandsHandlerInterface
{
    public function handleInteraction(array $params): array;
}
