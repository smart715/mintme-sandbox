<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\FacebookPostUrlValidator;
use PHPUnit\Framework\TestCase;

class FacebookPostUrlValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        ?string $url,
        bool $isValid
    ): void {
        $validator = new FacebookPostUrlValidator($url);
        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('airdrop_backend.invalid_facebook_url', $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "Valid with http" => [
                "url" => "http://www.facebook.com/TEST/posts/1234",
                "isValid" => true,
            ],
            "Valid with https" => [
                "url" => "https://www.facebook.com/TEST/posts/1234",
                "isValid" => true,
            ],
            "Invalid if null" => [
                "url" => null,
                "isValid" => false,
            ],
            "valid without https:// or http://" => [
               "www.facebook.com/TEST/posts/1234",
               true,
            ],
            "valid without www." => [
                "https://facebook.com/TEST/posts/1234",
                true,
            ],
            "Invalid Without non-whitespace between facebook and posts" => [
                "https://www.facebook.com/posts/1234",
                false,
            ],
            "invalid Without posts" => [
                "https://www.facebook.com/TEST",
                false,
            ],
            "invalid Without post id" => [
                "https://www.facebook.com/TEST/posts",
                false,
            ],
        ];
    }
}
