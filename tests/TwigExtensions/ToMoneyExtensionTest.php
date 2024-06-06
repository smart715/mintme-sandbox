<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\ToMoneyExtension;
use PHPUnit\Framework\TestCase;

class ToMoneyExtensionTest extends TestCase
{
    /** @dataProvider getTestCases */
    public function testToMoney(string $value, int $precision, bool $fixedPoint, string $expected): void
    {
        $stringExtension = new ToMoneyExtension();

        $this->assertEquals($expected, $stringExtension->toMoney($value, $precision, $fixedPoint));
    }

    public function getTestCases(): array
    {
        return [
            "Integer with 1 precision and no fixed point" => ["1", 1, false, "1"],
            "Integer with 4 precision and no fixed point" => ["999", 4, false, "999"],
            "Negative integer with 1 precision and no fixed point" => ["-1", 1, false, "-1"],
            "Negative integer with 4 precision and no fixed point" => ["-999", 4, false, "-999"],
            "Float with 1 precision and no fixed point" => ["1.1", 1, false, "1.1"],
            "Float with 4 precision and no fixed point" => ["999.9999", 4, false, "999.9999"],
            "Negative float with 1 precision and no fixed point" => ["-1.1", 1, false, "-1.1"],
            "Negative float with 4 precision and no fixed point" => ["-999.9999", 4, false, "-999.9999"],
            "Float with 1 precision and fixed point" => ["1.1", 1, true, "1.1"],
            "Float with 4 precision and fixed point" => ["999.9999", 4, true, "999.9999"],
            "Decimal with less precision than the fixed point" => ["1.1", 0, true, "1"],
            "Decimal with more precision than the fixed point" => ["999.999", 5, true, "999.99900"],
            "Negative number with less precision than the fixed point would round down" => ["-1.1", 0, true, "-2"],
            "Positive number with more precision than the fixed point would round down" => ["1.1", 5, true, "1.10000"],
        ];
    }
}
