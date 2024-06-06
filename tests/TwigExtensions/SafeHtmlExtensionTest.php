<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\SafeHtmlExtension;
use HTMLPurifier;
use PHPUnit\Framework\TestCase;

class SafeHtmlExtensionTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testSafeHtml(string $value, string $expected): void
    {
        $extension = new SafeHtmlExtension("test", []);

        $this->mockPurifier($extension);

        $this->assertEquals($expected, $extension->doSafeHtml($value));
    }

    public function getTestCases(): array
    {
        return [
            "value will get purified and checked for <a> tags" => [
                "<a href=\"https://www.example.com\">Example</a>",
                "<a href=\"https://www.example.com\" rel=\"noopener nofollow\" target=\"_blank\">Example</a>",
            ],
        ];
    }

    private function mockPurifier(SafeHtmlExtension $extension): void
    {
        $reflection = new \ReflectionClass($extension);
        $property = $reflection->getProperty('purifier');
        $property->setAccessible(true);
        $property->setValue($extension, $this->mockHTMLPurifier());
    }

    private function mockHTMLPurifier(): HTMLPurifier
    {
        $HTMLPurifier = $this->createMock(HTMLPurifier::class);

        $HTMLPurifier->expects($this->once())
            ->method('purify')
            ->willReturnArgument(0);

        return $HTMLPurifier;
    }
}
