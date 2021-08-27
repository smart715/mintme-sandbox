<?php declare(strict_types = 1);

namespace App\Utils;

class NotificationChannels implements NotificationChannelsInterface
{
    public const EMAIL = 'email';
    public const WEBSITE = 'website';

    public static function getAll(): array
    {
        return [
            self::EMAIL,
            self::WEBSITE,
        ];
    }
}
