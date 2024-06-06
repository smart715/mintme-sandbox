<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\NumberTruncateWithLetterExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class NumberTruncateWithLetterExtensionTest extends TestCase
{
    /** @dataProvider getTestCases
     * @param string|int|float $value
     * @param string $expected
     */
    public function testTruncate($value, string $expected): void
    {
        $stringExtension = new NumberTruncateWithLetterExtension();

        $this->assertEquals($expected, $stringExtension->doNumberTruncateWithLetter($value));
    }

    public function getTestCases(): array
    {
        return [
            "accepts integer" => ["value" => 1, "expected" => "1.0000"],
            "accepts float" => ["value" => 1, "expected" => "1.0000"],
            "accepts string" => ["value" => "1", "expected" => "1.0000"],
            "accepts string with float" => ["value" => "1.1", "expected" => "1.1000"],
            "accepts string with float with more than 4 digits" => ["value" => "1.12345", "expected" => "1.1234"],
            "Adds K letter for numbers equal 1 thousand" => ["value" => 1_000, "expected" => "1K"],
            "Adds M letter for numbers equal 1 million" => ["value" => 1_000_000, "expected" => "1M"],
            "Adds B letter for numbers equal 1 billion" => ["value" => 1_000_000_000, "expected" => "1B"],
            "Adds K letter for numbers above 1 thousand" => ["value" => 1_001, "expected" => "1.001K"],
            "Adds M letter for numbers above 1 million" => ["value" => 1_000_001, "expected" => "1.000M"],
            "Adds B letter for numbers above 1 billion" => ["value" => 1_000_000_001, "expected" => "1.000B"],
        ];
    }

    public function testGetFilers(): void
    {
        $stringExtension = new NumberTruncateWithLetterExtension();
        $filters = $stringExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertEquals('number_truncate_with_letter', $filters[0]->getName());
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
    }
}
