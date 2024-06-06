<?php declare(strict_types = 1);

namespace App\Utils;

/** @codeCoverageIgnore */
class NotificationChannels implements NotificationChannelsInterface
{
    public const EMAIL = 'email';
    public const WEBSITE = 'website';
    public const ADVANCED = 'advanced';

    public static function getAll(): array
    {
        return [
            self::EMAIL,
            self::WEBSITE,
            self::ADVANCED,
        ];
    }
}
