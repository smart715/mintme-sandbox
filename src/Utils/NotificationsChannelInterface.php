<?php declare(strict_types = 1);

namespace App\Utils;

interface NotificationsChannelInterface
{
    public static function getAll(): array;
}
