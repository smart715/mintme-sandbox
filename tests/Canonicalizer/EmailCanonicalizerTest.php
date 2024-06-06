<?php declare(strict_types = 1);

namespace App\Tests\Canonicalizer;

use App\Canonicalizer\EmailCanonicalizer;
use PHPUnit\Framework\TestCase;

class EmailCanonicalizerTest extends TestCase
{
    /**
     * @dataProvider canonicalizeProvider
     */
    public function testCanonicalize(?string $value, string $expected): void
    {
        $canonicalizer = new EmailCanonicalizer();
        $this->assertSame($expected, $canonicalizer->canonicalize($value));
    }

    public function canonicalizeProvider(): array
    {
        return [
            "gmail converted to googlemail" => [
                "blank@gmail.com",
                "blank@googlemail.com",
            ],
            "Dots in email are removed" => [
                "b.la.nk@gmail.com",
                "blank@googlemail.com",
            ],
            "if null, return empty string return empty string" => [
                null,
                "",
            ],
            "if non gmail, return same value" => [
                "test@protonmail.com",
                "test@protonmail.com",
            ],
        ];
    }
}
