<?php declare(strict_types = 1);

namespace App\Utils;

/** @codeCoverageIgnore */
abstract class NotificationTypes
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';
    public const TRANSACTION_DELAYED = 'transaction.delayed';
    public const NEW_INVESTOR = 'new_investor';
    public const NEW_BUY_ORDER = 'new_buy_order';
    public const TOKEN_NEW_POST = 'new_post';
    public const TOKEN_DEPLOYED = 'deployed';
    public const ORDER_FILLED = 'filled';
    public const ORDER_CANCELLED = 'cancelled';
    public const TOKEN_MARKETING_TIPS = 'token_marketing_tips';
    public const TOKEN_PROMOTION = 'token_promotion';
    public const MARKETING_AIRDROP_FEATURE = 'marketing_airdrop_feature';
    public const REWARD_PARTICIPANT = 'reward.participant';
    public const REWARD_VOLUNTEER_NEW = 'reward.volunteer.new';
    public const REWARD_VOLUNTEER_ACCEPTED = 'reward.volunteer.accepted';
    public const REWARD_VOLUNTEER_COMPLETED = 'reward.volunteer.completed';
    public const REWARD_VOLUNTEER_REJECTED = 'reward.volunteer.rejected';
    public const REWARD_PARTICIPANT_REJECTED = 'reward.participant.rejected';
    public const REWARD_PARTICIPANT_DELIVERED = 'reward.participant.delivered';
    public const REWARD_PARTICIPANT_REFUNDED = 'reward.participant.refunded';
    public const REWARD_NEW = 'reward.new';
    public const BOUNTY_NEW = 'bounty.new';
    public const MARKET_CREATED = 'market.created';

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
            self::REWARD_PARTICIPANT,
            self::REWARD_VOLUNTEER_NEW,
            self::REWARD_VOLUNTEER_ACCEPTED,
            self::REWARD_VOLUNTEER_REJECTED,
            self::REWARD_PARTICIPANT_REJECTED,
            self::REWARD_PARTICIPANT_DELIVERED,
            self::REWARD_PARTICIPANT_REFUNDED,
            self::REWARD_NEW,
            self::MARKET_CREATED,
            self::NEW_BUY_ORDER,
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
