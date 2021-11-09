<?php declare(strict_types = 1);

namespace App\Communications;

interface DiscordOAuthClientInterface
{
    public const BOT_PERMISSIONS_ADMINISTRATOR = 8;

    public function getAccessToken(string $code, string $redirectUrl): string;

    public function generateAuthUrl(
        string $scope,
        string $redirectUrl,
        ?int $permissions = null,
        ?string $state = null
    ): string;
}
