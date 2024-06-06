<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\RebrandingExtension;
use PHPUnit\Framework\TestCase;

class RebrandingExtensionTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testRebrandingExtension(string $value, string $expected): void
    {
        $extension = new RebrandingExtension();

        $this->assertEquals($expected, $extension->doRebranding($value));
    }

    public function getTestCases(): array
    {
        return [
            "Webchain will be replaced by MintMe Coin" => [
                "Webchain", "MintMe Coin",
            ],
            "webchain will be replaced by mintMe Coin" => [
                "webchain", "mintMe Coin",
            ],
            "WEB will be replaced by MINTME" => [
                "WEB", "MINTME",
            ],
            "web will be replaced by mintme" => [
                "web", "MINTME",
            ],
        ];
    }
}
