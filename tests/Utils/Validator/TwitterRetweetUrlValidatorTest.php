<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\TwitterRetweetUrlValidator;
use PHPUnit\Framework\TestCase;

class TwitterRetweetUrlValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        ?string $url,
        bool $isValid
    ): void {
        $validator = new TwitterRetweetUrlValidator($url);

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('airdrop_backend.invalid_twitter_url', $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "Valid retweet" => [
                "https://twitter.com/twitter/1231/status/1234",
                true,
            ],
            "Valid if protocol is http retweet" => [
                "https://twitter.com/twitter/1231/status/1234",
                true,
            ],
            "Valid if it didn't include protocol" => [
                "www.twitter.com/twitter/1231/status/1234",
                true,
            ],
            "Valid if it didn't include subdomain" => [
                "https://twitter.com/twitter/1231/status/1234",
                true,
            ],
            "Invalid if it didn't include twitter.com" => [
                "https://twitter.net/1231/status/1234",
                false,
            ],
            "Invalid if it didn't include status" => [
                "https://twitter.com/twitter/1231/1234",
                false,
            ],
            "Invalid if it didn't include twitter/id" => [
                "https://twitter.com/status/1234",
                false,
            ],
            "Invalid if it didn't include status id" => [
                "https://twitter.com/twitter/1231/status",
                false,
            ],
            "Invalid if url is null" => [
                null,
                false,
            ],
        ];
    }
}
