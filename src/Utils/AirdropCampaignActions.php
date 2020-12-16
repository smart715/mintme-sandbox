<?php declare(strict_types = 1);

namespace App\Utils;

abstract class AirdropCampaignActions
{
    public const TWITTER_MESSAGE = 'twitterMessage';
    public const TWITTER_RETWEET = 'twitterRetweet';
    public const FACEBOOK_MESSAGE = 'facebookMessage';
    public const FACEBOOK_PAGE = 'facebookPage';
    public const FACEBOOK_POST = 'facebookPost';
    public const LINKEDIN_MESSAGE = 'linkedinMessage';
    public const YOUTUBE_SUBSCRIBE = 'youtubeSubscribe';
    public const POST_LINK = 'postLink';

    private static function getAll(): array
    {
        return [
            self::TWITTER_MESSAGE,
            self::TWITTER_RETWEET,
            self::FACEBOOK_MESSAGE,
            self::FACEBOOK_PAGE,
            self::FACEBOOK_POST,
            self::LINKEDIN_MESSAGE,
            self::YOUTUBE_SUBSCRIBE,
            self::POST_LINK,
        ];
    }

    public static function isValid(string $action): bool
    {
        $actions = self::getAll();

        foreach ($actions as $item) {
            if ($action === $item) {
                return true;
            }
        }

        return false;
    }
}
