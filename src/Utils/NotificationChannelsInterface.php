<?php declare(strict_types = 1);

namespace App\Utils;

interface NotificationChannelsInterface
{
    public static function getAll(): array;
}
