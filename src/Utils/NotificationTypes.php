<?php declare(strict_types = 1);

namespace App\Utils;

class NotificationTypes implements NotificationTypesInterface
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';
    public const NEW_INVESTOR = 'new_investor';
    public const TOKEN_NEW_POST = 'new_post';
    public const TOKEN_DEPLOYED = 'deployed';
    public const ORDER_FILLED = 'filled';
    public const ORDER_CANCELLED = 'cancelled';

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
    public static function getText(): array
    {
        return [
            self::DEPOSIT => 'Deposits',
            self::WITHDRAWAL => 'Withdrawals',
            self::NEW_INVESTOR => 'New Investors',
            self::TOKEN_NEW_POST => 'Posts related to token you own',
            self::TOKEN_DEPLOYED => 'Deployments related to token you own',
            self::ORDER_FILLED => 'Orders filled',
            self::ORDER_CANCELLED => 'Orders Cancelled',
        ];
    }
}
