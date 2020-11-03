<?php declare(strict_types = 1);

namespace App\Utils;

use ReflectionClass;

class NotificationsType implements NotificationsTypeInterface
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';
    public const NEW_INVESTOR = 'new_investor';
    public const TOKEN_NEW_POST = 'new_post';
    public const TOKEN_DEPLOYED =  'deployed';
    public const ORDER_FILLED =  'filled';
    public const ORDER_CANCELLED =  'cancelled';



    public static function getAll(): array
    {
        $oClass = new ReflectionClass(self::class);

        return $oClass->getConstants();
    }
}
