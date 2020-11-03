<?php declare(strict_types = 1);

namespace App\Utils;

use ReflectionClass;

class NotificationsChannel implements NotificationsChannelInterface
{
    public const EMAIL = 'email';
    public const WEBSITE = 'website';

    public static function getAll(): array
    {
        $channelClasses = new ReflectionClass(self::class);

        return $channelClasses->getConstants();
    }
}
