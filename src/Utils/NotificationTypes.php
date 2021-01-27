<?php declare(strict_types = 1);

namespace App\Utils;

abstract class NotificationTypes
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';
    public const NEW_INVESTOR = 'new_investor';
    public const TOKEN_NEW_POST = 'new_post';
    public const TOKEN_DEPLOYED = 'deployed';
    public const ORDER_FILLED = 'filled';
    public const ORDER_CANCELLED = 'cancelled';

    public const ORDER_TYPES = [
        self::ORDER_CANCELLED,
        self::ORDER_FILLED,
    ];

    public static function getAll(): array
    {
        return [
            self::DEPOSIT,
            self::WITHDRAWAL,
            self::NEW_INVESTOR,
            self::TOKEN_NEW_POST,
            self::TOKEN_DEPLOYED,
            self::ORDER_FILLED,
            self::ORDER_CANCELLED,
        ];
    }
    public static function getConfigurable(): array
    {
        return [
            self::DEPOSIT,
            self::WITHDRAWAL,
            self::NEW_INVESTOR,
            self::TOKEN_NEW_POST,
            self::TOKEN_DEPLOYED,
        ];
    }
}
