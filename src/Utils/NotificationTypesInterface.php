<?php declare(strict_types = 1);

namespace App\Utils;

interface NotificationTypesInterface
{
    public static function getAll(): array;
    public static function getConfigurable(): array;
    public function getText(): array;
    public static function getStrategyText(): array;
}
