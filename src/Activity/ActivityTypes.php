<?php declare(strict_types = 1);

namespace App\Activity;

/** @codeCoverageIgnore */
final class ActivityTypes
{
    public const AIRDROP_CLAIMED = 0;
    public const AIRDROP_CREATED = 1;
    public const AIRDROP_ENDED = 2;
    public const DONATION = 3;
    public const NEW_POST = 4;
    public const TOKEN_DEPLOYED = 6;
    public const DEPOSITED = 7;
    public const TOKEN_TRADED = 8;
    public const WITHDRAWN = 9;
    public const REWARD_NEW = 10;
    public const BOUNTY_NEW = 11;
    public const REWARD_NEW_VOLUNTEER = 12;
    public const REWARD_NEW_PARTICIPANT = 13;
    public const MARKET_CREATED = 14;
    public const TOKEN_CONNECTED = 15;
    public const TOKEN_NEW_DM = 16;
    public const POST_COMMENTED = 17;
    public const POST_LIKE = 18;
    public const COMMENT_LIKE = 19;
    public const USER_REGISTERED = 20;
    public const TOKEN_CREATED = 21;
    public const TOKEN_ADDED = 22;
    public const SIGN_UP_BONUS_CREATED = 23;
    public const PROPOSITION_ADDED = 24;
    public const USER_VOTED = 25;
    public const USER_FOLLOWED = 26;
    public const PROJECT_DESCRIPTION_UPDATED = 27;
    public const SOCIAL_MEDIA_UPDATED = 28;
    public const TOKEN_SHARED = 29;
    public const PHONE_VERIFIED = 30;
    public const TIP_RECEIVED = 31;
    public const BOUNTY_ACCEPTED = 32;
    public const SIGN_UP_CAMPAIGN = 33;
    public const AIRDROP_REFERRAL = 34;
    public const BOUNTY_PAID = 35;
    public const DISCORD_REWARDS_ADDED = 36;
    public const DISCORD_REWARD_RECEIVED = 37;
    public const TOKEN_RELEASE_SET = 38;
    private function __construct()
    {
    }
}
