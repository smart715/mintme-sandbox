<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Token\Token;
use App\Utils\Validator\AirdropCampaignActionsValidator;
use PHPUnit\Framework\TestCase;

class AirdropCampaignActionsValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(?array $actions, array $actionsData, Token $token, string $message, bool $isValid): void
    {
        $validator = new AirdropCampaignActionsValidator($actions, $actionsData, $token);
        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "Invalid if actions are null" => [
                "actions" => null,
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => false,
            ],
            "Invalid if action are not supported" => [
                "actions" => ["TEST" => true],
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => false,
            ],
            "Invalid if action active value isn't boolean" => [
                "actions" => ["twitterRetweet" => "TEST"],
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => false,
            ],
            "Invalid if action isn't active" => [
                "actions" => ["twitterRetweet" => false],
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => false,
            ],
            "Valid twitterRetweet action" => [
                "actions" => ["twitterRetweet" => true],
                "actionsData" => ["twitterRetweet" => "www.twitter.com/twitter/1231/status/1234"],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => true,
            ],
            "Invalid twitterRetweet action" => [
                "actions" => ["twitterRetweet" => true],
                "actionsData" => ["twitterRetweet" => "www.twitter.com/twitter/1231/status"],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_twitter_url",
                "isValid" => false,
            ],
            "Valid facebookPost action" => [
                "actions" => ["facebookPost" => true],
                "actionsData" => ["facebookPost" => "https://www.facebook.com/12313123/posts/123131231"],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => true,
            ],
            "Invalid facebookPost action" => [
                "actions" => ["facebookPost" => true],
                "actionsData" => ["facebookPost" => "https://www.facebook.com/12313123/posts"],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_facebook_url",
                "isValid" => false,
            ],
            "Valid facebookPage action" => [
                "actions" => ["facebookPage" => true],
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => true,
            ],
            "Invalid facebookPage action" => [
                "actions" => ["facebookPage" => true],
                "actionsData" => [],
                "token" => $this->mockToken(false),
                "message" => "airdrop_backend.invalid_facebook_page",
                "isValid" => false,
            ],
            "Valid youtubeSubscribe action" => [
                "actions" => ["youtubeSubscribe" => true],
                "actionsData" => [],
                "token" => $this->mockToken(),
                "message" => "airdrop_backend.invalid_actions",
                "isValid" => true,
            ],
            "Invalid youtubeSubscribe action" => [
                "actions" => ["youtubeSubscribe" => true],
                "actionsData" => [],
                "token" => $this->mockToken(true, false),
                "message" => "airdrop_backend.invalid_youtube_channel",
                "isValid" => false,
            ],
        ];
    }

    private function mockToken(bool $haveFacebookUrl = true, bool $haveYoutubeChannelId = true): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getFacebookUrl')->willReturn($haveFacebookUrl ? "TEST" : null);
        $token->method('getYoutubeChannelId')->willReturn($haveYoutubeChannelId ? "TEST" : null);

        return $token;
    }
}
