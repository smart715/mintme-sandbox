<?php declare(strict_types = 1);

namespace App\Tests\Admin\Traits;

use App\Admin\Traits\CheckContentLinksTrait;
use PHPUnit\Framework\TestCase;

class CheckContentLinksTraitTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testCheckContentLinks(string $content, string $expected, bool $contentChanged): void
    {
        /** @var Object */
        $checkContentLinksTrait = $this->getObjectForTrait(CheckContentLinksTrait::class);
        $value = $checkContentLinksTrait->addNoopenerNofollowToLinks(  /* @phpstan-ignore-line */
            $content,
            $this->getInternalLinksArray(),
        );

        $this->assertEquals($expected, $value["content"]);
        $this->assertEquals($contentChanged, $value["contentChanged"]);
    }

    public function getTestCases(): array
    {
        return [
            "a tags rel set to 'noopener nofollow', attribute set to blank if link isn't dev or prod" => [
                "<a href=\"https://www.example.com\">Example</a>",
                "<a href=\"https://www.example.com\" rel=\"noopener nofollow\" target=\"_blank\">Example</a>",
                true,
            ],
            "a tags doesn't change if everything is set" => [
                "<a href=\"https://www.example.com\" rel=\"noopener nofollow\" target=\"_blank\">Example</a>",
                "<a href=\"https://www.example.com\" rel=\"noopener nofollow\" target=\"_blank\">Example</a>",
                true,
            ],
            "a tag doesn't target blank if it's dev or prod" => [
                "<a href=\"https://www.mintme.com\">Example</a>",
                "<a href=\"https://www.mintme.com\">Example</a>",
                true,
            ],
            "content doesn't change if a tag doesn't exist" => [
                "Example",
                "Example",
                false,
            ],
        ];
    }
    
    private function getInternalLinksArray(): array
    {
        return [
            'mintme.com',
            'mintme.host',
            'coinimp.com',
            'webchain.network',
            'cba.pl',
            'zzz.com',
            'zz.com.ve',
            'hit.ng',
            'lea.mx',
            'aba.ae',
            'for.ug',
            'mintme.abchosting.abc',
        ];
    }
}
