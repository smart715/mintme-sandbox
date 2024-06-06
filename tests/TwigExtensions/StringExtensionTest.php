<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\StringExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class StringExtensionTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testTruncate(string $value, string $expected): void
    {
        $stringExtension = new StringExtension();

        $this->assertEquals($expected, $stringExtension->dashedString($value));
    }

    public function getTestCases(): array
    {
        return [
            "spaces converts to dashes" => ["Hello world", "Hello-world"],
            "dashes are not converted" => ["Hello-world", "Hello-world"],
            "symbols are not converted" => ["*Hello||| world!", "*Hello|||-world!"],
        ];
    }

    public function testGetFilers(): void
    {
        $stringExtension = new StringExtension();
        $filters = $stringExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertEquals('dashedString', $filters[0]->getName());
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
    }
}
