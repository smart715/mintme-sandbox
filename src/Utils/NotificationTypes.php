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
    public const TOKEN_MARKETING_TIPS = 'token_marketing_tips';
    public const MARKETING_AIRDROP_FEATURE = 'marketing_airdrop_feature';

    public const ORDER_TYPES = [
        self::ORDER_CANCELLED,
        self::ORDER_FILLED,
    ];

    public const MARKETING_TYPES = [
        self::TOKEN_MARKETING_TIPS,
        self::MARKETING_AIRDROP_FEATURE,
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
            self::TOKEN_MARKETING_TIPS,
            self::MARKETING_AIRDROP_FEATURE,
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
            self::TOKEN_MARKETING_TIPS,
        ];
    }
}
