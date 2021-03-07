<?php declare(strict_types = 1);

namespace App\Events;

final class TokenEvents
{
    public const CREATED = "token.created";
    public const DEPLOYED = "token.deployed";
    public const AIRDROP_CREATED = "airdrop.created";
    public const AIRDROP_CLAIMED = "airdrop.claimed";
    public const AIRDROP_ENDED = "airdrop.ended";
    public const POST_CREATED = "post.created";
    public const DONATION = "donation";

    private function __construct()
    {
    }
}
