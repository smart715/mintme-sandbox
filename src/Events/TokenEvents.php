<?php declare(strict_types = 1);

namespace App\Events;

/** @codeCoverageIgnore */
final class TokenEvents
{
    public const CREATED = "token.created";
    public const DEPLOYED = "token.deployed";
    public const CONNECTED = "token.connected";
    public const AIRDROP_CREATED = "airdrop.created";
    public const AIRDROP_CLAIMED = "airdrop.claimed";
    public const AIRDROP_ENDED = "airdrop.ended";
    public const POST_CREATED = "post.created";
    public const POST_SHARED = "post.shared";
    public const POST_COMMENTED = "post.commented";
    public const POST_LIKED = "post.liked";
    public const COMMENT_LIKE = "comment.liked";
    public const DONATION = "donation";
    public const MARKET_CREATED = "market.created";
    public const NEW_DM = "new.dm";

    private function __construct()
    {
    }
}
